<?php
// 数据库配置
define('DB_HOST', 'db');
define('DB_USER', 'notice_user');
define('DB_PASS', 'notice_pass');
define('DB_NAME', 'notice_db');

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

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
?>
