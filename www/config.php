<?php
$_ENV_LOADED = [];
function loadEnv() {
    global $_ENV_LOADED;
    if (!empty($_ENV_LOADED)) return;
    $path = dirname(__DIR__) . '/.env';
    if (!is_file($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        $eq = strpos($line, '=');
        if ($eq === false) continue;
        $key = trim(substr($line, 0, $eq));
        $val = trim(substr($line, $eq + 1));
        if (strlen($val) >= 2 && (($val[0] === '"' && $val[strlen($val)-1] === '"') || ($val[0] === "'" && $val[strlen($val)-1] === "'"))) {
            $val = substr($val, 1, -1);
        }
        $_ENV_LOADED[$key] = $val;
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}
function env(string $key, string $default = ''): string {
    if (isset($_ENV_LOADED[$key])) return $_ENV_LOADED[$key];
    $v = getenv($key);
    return ($v !== false) ? $v : $default;
}
loadEnv();

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'notice_db'));

// 创建数据库连接
function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("连接失败: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 关闭数据库连接
function closeConnection($conn) {
    if ($conn) {
        $conn->close();
    }
}

// 安全处理输入
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// 格式化日期
function formatDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

function ensureFeedbackTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS feedbacks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_no VARCHAR(8) NOT NULL UNIQUE,
        type ENUM('bug', 'feature', 'complaint', 'suggestion', 'other') NOT NULL DEFAULT 'other',
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        contact VARCHAR(255) DEFAULT '',
        screenshots TEXT DEFAULT NULL,
        status ENUM('pending', 'processing', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
        internal_note TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_ticket_no (ticket_no),
        INDEX idx_status (status),
        INDEX idx_type (type),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS feedback_timeline (
        id INT AUTO_INCREMENT PRIMARY KEY,
        feedback_id INT NOT NULL,
        from_status VARCHAR(20) DEFAULT NULL,
        to_status VARCHAR(20) NOT NULL,
        note TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_feedback_id (feedback_id),
        FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    closeConnection($conn);
}

function ensureQATables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notice_id INT NOT NULL,
        asker VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        status ENUM('open', 'resolved') DEFAULT 'open',
        best_answer_id INT DEFAULT NULL,
        views INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_notice_id (notice_id),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        answerer VARCHAR(100) NOT NULL,
        content TEXT NOT NULL,
        likes INT DEFAULT 0,
        is_best TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_question_id (question_id),
        INDEX idx_is_best (is_best),
        FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $conn->query("CREATE TABLE IF NOT EXISTS answer_likes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        answer_id INT NOT NULL,
        user_identifier VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_answer_user (answer_id, user_identifier),
        FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    closeConnection($conn);
}

function getUserIdentifier() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    return md5($ip . '|' . $ua);
}

function ensurePrintTemplates() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS print_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        header_text VARCHAR(255) DEFAULT '',
        footer_text VARCHAR(255) DEFAULT '',
        logo_url VARCHAR(500) DEFAULT '',
        style_json TEXT DEFAULT NULL,
        is_default TINYINT(1) DEFAULT 0,
        template_type ENUM('minimal', 'official', 'card') DEFAULT 'minimal',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_name (name),
        INDEX idx_is_default (is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $result = $conn->query("SELECT COUNT(*) as cnt FROM print_templates");
    $row = $result->fetch_assoc();
    if ($row['cnt'] == 0) {
        $defaultStyle1 = json_encode(['fontFamily' => 'Noto Sans SC, sans-serif', 'fontSize' => '14px', 'textColor' => '#333', 'backgroundColor' => '#fff', 'borderColor' => '#e5e7eb', 'primaryColor' => '#3b82f6', 'headerBgColor' => '#f9fafb', 'footerBgColor' => '#f9fafb']);
        $defaultStyle2 = json_encode(['fontFamily' => 'SimSun, Noto Sans SC, sans-serif', 'fontSize' => '16px', 'textColor' => '#111', 'backgroundColor' => '#fff', 'borderColor' => '#333', 'primaryColor' => '#1e40af', 'headerBgColor' => '#fff', 'footerBgColor' => '#fff', 'titleFontSize' => '28px', 'titleAlign' => 'center', 'borderStyle' => 'double']);
        $defaultStyle3 = json_encode(['fontFamily' => 'Noto Sans SC, sans-serif', 'fontSize' => '14px', 'textColor' => '#374151', 'backgroundColor' => '#f3f4f6', 'cardBgColor' => '#fff', 'borderRadius' => '12px', 'borderColor' => '#e5e7eb', 'primaryColor' => '#8b5cf6', 'shadow' => '0 4px 6px -1px rgba(0,0,0,0.1)', 'padding' => '24px']);
        $conn->query("INSERT INTO print_templates (name, header_text, footer_text, logo_url, style_json, is_default, template_type) VALUES
            ('极简风', '公告信息管理系统', '© 2024 公告信息管理系统. All rights reserved.', '', '" . $conn->real_escape_string($defaultStyle1) . "', 1, 'minimal'),
            ('正式公文', '公 告', '发布单位：公告信息管理系统', '', '" . $conn->real_escape_string($defaultStyle2) . "', 0, 'official'),
            ('卡片风', '', '感谢您的关注', '', '" . $conn->real_escape_string($defaultStyle3) . "', 0, 'card')");
    }
    closeConnection($conn);
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function ensureBackupTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS backup_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        display_name VARCHAR(255) NOT NULL,
        file_size BIGINT NOT NULL DEFAULT 0,
        backup_type ENUM('manual', 'auto_pre_restore') NOT NULL DEFAULT 'manual',
        remark TEXT DEFAULT NULL,
        status ENUM('success', 'failed', 'processing') NOT NULL DEFAULT 'success',
        error_log TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_backup_type (backup_type),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    closeConnection($conn);
}

function getBackupDir() {
    $dir = __DIR__ . '/backups';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function ensureSubscriptionTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        sub_type ENUM('category', 'author', 'keyword', 'priority') NOT NULL,
        sub_value VARCHAR(255) NOT NULL,
        is_paused TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_sub_type (sub_type),
        INDEX idx_is_paused (is_paused)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS push_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subscription_id INT NOT NULL,
        notice_id INT NOT NULL,
        summary VARCHAR(500) NOT NULL,
        push_status ENUM('generated', 'sent', 'failed') DEFAULT 'generated',
        pushed_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_subscription_id (subscription_id),
        INDEX idx_notice_id (notice_id),
        INDEX idx_push_status (push_status),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
        FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $result = $conn->query("SHOW COLUMNS FROM notices LIKE 'category'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE notices ADD COLUMN category VARCHAR(100) DEFAULT '' COMMENT '公告分类' AFTER author");
        $conn->query("ALTER TABLE notices ADD INDEX idx_category (category)");
    }

    $result = $conn->query("SHOW COLUMNS FROM notices LIKE 'survey_id'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE notices ADD COLUMN survey_id INT DEFAULT NULL COMMENT '关联问卷ID' AFTER status");
        $conn->query("ALTER TABLE notices ADD INDEX idx_survey_id (survey_id)");
    }

    closeConnection($conn);
}

function ensureChatTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS chat_rooms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description VARCHAR(500) DEFAULT '',
        is_default TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_is_default (is_default)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $result = $conn->query("SELECT COUNT(*) as cnt FROM chat_rooms WHERE is_default = 1");
    $row = $result->fetch_assoc();
    if ($row['cnt'] == 0) {
        $conn->query("INSERT INTO chat_rooms (name, description, is_default) VALUES ('公共答疑大厅', '系统默认公共答疑房间，所有人进入后自动加入', 1)");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS chat_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        nickname VARCHAR(100) NOT NULL,
        user_identifier VARCHAR(255) NOT NULL,
        message_type ENUM('text', 'image', 'system') NOT NULL DEFAULT 'text',
        content TEXT NOT NULL,
        mention_nickname VARCHAR(100) DEFAULT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_room_id (room_id),
        INDEX idx_created_at (created_at),
        INDEX idx_room_created (room_id, id),
        FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS chat_users_online (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT NOT NULL,
        nickname VARCHAR(100) NOT NULL,
        user_identifier VARCHAR(255) NOT NULL,
        is_admin TINYINT(1) DEFAULT 0,
        last_heartbeat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_room_user (room_id, user_identifier),
        INDEX idx_room_id (room_id),
        INDEX idx_last_heartbeat (last_heartbeat),
        FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    closeConnection($conn);
}

function ensureSurveyTables() {
    $conn = getConnection();

    $conn->query("CREATE TABLE IF NOT EXISTS surveys (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        start_time DATETIME DEFAULT NULL,
        end_time DATETIME DEFAULT NULL,
        is_enabled TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_is_enabled (is_enabled),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS survey_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        question_text TEXT NOT NULL,
        question_type ENUM('single', 'multiple', 'text') NOT NULL,
        sort_order INT DEFAULT 0,
        is_required TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_survey_id (survey_id),
        INDEX idx_sort_order (sort_order),
        FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS survey_options (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question_id INT NOT NULL,
        option_text VARCHAR(500) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_question_id (question_id),
        INDEX idx_sort_order (sort_order),
        FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS survey_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        survey_id INT NOT NULL,
        question_id INT NOT NULL,
        visitor_id VARCHAR(255) NOT NULL,
        option_id INT DEFAULT NULL,
        answer_text TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_survey_id (survey_id),
        INDEX idx_question_id (question_id),
        INDEX idx_visitor_id (visitor_id),
        INDEX idx_created_at (created_at),
        UNIQUE KEY uk_survey_visitor_question (survey_id, visitor_id, question_id),
        FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
        FOREIGN KEY (option_id) REFERENCES survey_options(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $result = $conn->query("SHOW COLUMNS FROM notices LIKE 'survey_id'");
    if ($result && $result->num_rows == 0) {
        $conn->query("ALTER TABLE notices ADD COLUMN survey_id INT DEFAULT NULL COMMENT '关联问卷ID' AFTER status");
        $conn->query("ALTER TABLE notices ADD INDEX idx_survey_id (survey_id)");
    }

    closeConnection($conn);
}

function ensureRatingTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS notice_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        notice_id INT NOT NULL,
        visitor_id VARCHAR(255) NOT NULL,
        score TINYINT(1) NOT NULL,
        comment TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_notice_visitor (notice_id, visitor_id),
        INDEX idx_notice_id (notice_id),
        INDEX idx_visitor_id (visitor_id),
        INDEX idx_score (score),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    closeConnection($conn);
}

function ensureLotteryTables() {
    $conn = getConnection();
    $conn->query("CREATE TABLE IF NOT EXISTS lotteries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        notice_id INT DEFAULT NULL,
        prizes JSON NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        draw_time DATETIME NOT NULL,
        condition_text TEXT DEFAULT NULL,
        status ENUM('draft', 'active', 'paused', 'finished') DEFAULT 'draft',
        is_drawn TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_notice_id (notice_id),
        INDEX idx_start_time (start_time),
        INDEX idx_draw_time (draw_time),
        INDEX idx_is_drawn (is_drawn),
        FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS lottery_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lottery_id INT NOT NULL,
        visitor_id VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_lottery_visitor (lottery_id, visitor_id),
        INDEX idx_lottery_id (lottery_id),
        INDEX idx_visitor_id (visitor_id),
        INDEX idx_created_at (created_at),
        FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS lottery_winners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lottery_id INT NOT NULL,
        prize_name VARCHAR(255) NOT NULL,
        visitor_id VARCHAR(255) NOT NULL,
        drawn_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lottery_id (lottery_id),
        INDEX idx_visitor_id (visitor_id),
        INDEX idx_drawn_at (drawn_at),
        FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    closeConnection($conn);
}

function getBackupTables() {
    return ['notices', 'feedbacks', 'feedback_timeline', 'questions', 'answers', 'answer_likes', 'print_templates', 'backup_records', 'subscriptions', 'push_records', 'surveys', 'survey_questions', 'survey_options', 'survey_answers', 'chat_rooms', 'chat_messages', 'chat_users_online', 'notice_ratings', 'lotteries', 'lottery_participants', 'lottery_winners'];
}

function matchSubscription($sub, $notice) {
    $type = $sub['sub_type'];
    $value = $sub['sub_value'];
    switch ($type) {
        case 'category':
            return mb_strpos($notice['category'] ?? '', $value, 0, 'UTF-8') !== false;
        case 'author':
            return mb_strpos($notice['author'] ?? '', $value, 0, 'UTF-8') !== false;
        case 'keyword':
            return mb_strpos($notice['title'] ?? '', $value, 0, 'UTF-8') !== false
                || mb_strpos($notice['content'] ?? '', $value, 0, 'UTF-8') !== false;
        case 'priority':
            return ($notice['priority'] ?? '') === $value;
        default:
            return false;
    }
}

function generatePushRecordsForNotice($noticeId) {
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT id, title, content, author, category, priority FROM notices WHERE id = ?");
    $stmt->bind_param('i', $noticeId);
    $stmt->execute();
    $notice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$notice) {
        closeConnection($conn);
        return 0;
    }

    $result = $conn->query("SELECT * FROM subscriptions WHERE is_paused = 0");
    $subscriptions = [];
    while ($row = $result->fetch_assoc()) {
        $subscriptions[] = $row;
    }

    $summary = mb_substr(strip_tags($notice['content']), 0, 200, 'UTF-8');
    $count = 0;

    foreach ($subscriptions as $sub) {
        if (matchSubscription($sub, $notice)) {
            $check = $conn->prepare("SELECT id FROM push_records WHERE subscription_id = ? AND notice_id = ?");
            $check->bind_param('ii', $sub['id'], $noticeId);
            $check->execute();
            $exists = $check->get_result()->fetch_assoc();
            $check->close();

            if (!$exists) {
                $insert = $conn->prepare("INSERT INTO push_records (subscription_id, notice_id, summary, push_status, pushed_at) VALUES (?, ?, ?, 'generated', NULL)");
                $insert->bind_param('iis', $sub['id'], $noticeId, $summary);
                if ($insert->execute()) {
                    $count++;
                }
                $insert->close();
            }
        }
    }

    closeConnection($conn);
    return $count;
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
