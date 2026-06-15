-- 设置字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 创建数据库
CREATE DATABASE IF NOT EXISTS notice_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE notice_db;

-- 创建公告信息表
CREATE TABLE IF NOT EXISTS notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '公告标题',
    content TEXT NOT NULL COMMENT '公告内容',
    author VARCHAR(100) NOT NULL COMMENT '发布人',
    category VARCHAR(100) DEFAULT '' COMMENT '公告分类',
    publish_date DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
    update_date DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    status ENUM('published', 'draft') DEFAULT 'published' COMMENT '状态',
    survey_id INT DEFAULT NULL COMMENT '关联问卷ID',
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
    views INT DEFAULT 0 COMMENT '浏览次数',
    INDEX idx_publish_date (publish_date),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_survey_id (survey_id),
    INDEX idx_status_publish_date (status, publish_date),
    INDEX idx_priority_publish_date (priority, publish_date),
    INDEX idx_author_publish_date (author, publish_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入示例数据
INSERT INTO notices (title, content, author, category, priority, status, publish_date) VALUES
('欢迎使用公告信息管理系统', '这是一个功能完善的公告信息管理系统，支持添加、编辑、删除和查询公告信息。', '系统管理员', '系统公告', 'high', 'published', NOW()),
('系统维护通知', '本系统将于本周六进行例行维护，维护时间为凌晨2:00-6:00，期间系统将暂停服务。', '技术部', '运维通知', 'high', 'published', NOW()),
('新功能上线', '我们很高兴地宣布，系统新增了分页显示和高级搜索功能，欢迎体验！', '产品部', '产品更新', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 1 DAY)),
('安全提醒', '请各位用户定期修改密码，确保账户安全。如发现异常情况，请及时联系管理员。', '安全部', '安全通知', 'high', 'published', DATE_SUB(NOW(), INTERVAL 2 DAY)),
('假期安排通知', '根据国家法定节假日安排，本系统将在春节期间正常运行，技术支持团队将保持在线。', '人事部', '人事通知', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 3 DAY)),
('用户调查问卷', '为了更好地改进我们的服务，诚邀您参与用户满意度调查，您的意见对我们非常重要。', '客服部', '调查问卷', 'low', 'published', DATE_SUB(NOW(), INTERVAL 5 DAY)),
('培训课程通知', '本月将举办系统使用培训课程，欢迎新用户报名参加，详情请查看培训中心。', '培训部', '培训通知', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 7 DAY)),
('版本更新说明', '系统已更新至v2.0版本，新增了数据导出、批量操作等功能，提升了系统性能。', '技术部', '产品更新', 'medium', 'published', DATE_SUB(NOW(), INTERVAL 10 DAY));

-- 创建访客反馈表
CREATE TABLE IF NOT EXISTS feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_no VARCHAR(8) NOT NULL UNIQUE COMMENT '工单号',
    type ENUM('bug', 'feature', 'complaint', 'suggestion', 'other') NOT NULL DEFAULT 'other' COMMENT '反馈类型',
    title VARCHAR(255) NOT NULL COMMENT '反馈标题',
    description TEXT NOT NULL COMMENT '反馈描述',
    contact VARCHAR(255) DEFAULT '' COMMENT '联系方式',
    screenshots TEXT DEFAULT NULL COMMENT '截图URL列表，JSON数组',
    status ENUM('pending', 'processing', 'resolved', 'closed') NOT NULL DEFAULT 'pending' COMMENT '状态',
    internal_note TEXT DEFAULT NULL COMMENT '内部备注',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后处理时间',
    INDEX idx_ticket_no (ticket_no),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建反馈状态变更时间线表
CREATE TABLE IF NOT EXISTS feedback_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL COMMENT '反馈ID',
    from_status VARCHAR(20) DEFAULT NULL COMMENT '变更前状态',
    to_status VARCHAR(20) NOT NULL COMMENT '变更后状态',
    note TEXT DEFAULT NULL COMMENT '备注',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '变更时间',
    INDEX idx_feedback_id (feedback_id),
    FOREIGN KEY (feedback_id) REFERENCES feedbacks(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建问题表
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL COMMENT '公告ID',
    asker VARCHAR(100) NOT NULL COMMENT '提问者',
    content TEXT NOT NULL COMMENT '提问内容',
    status ENUM('open', 'resolved') DEFAULT 'open' COMMENT '状态：open未解答，resolved已解答',
    best_answer_id INT DEFAULT NULL COMMENT '最佳答案ID',
    views INT DEFAULT 0 COMMENT '浏览次数',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '提问时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_notice_id (notice_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建回答表
CREATE TABLE IF NOT EXISTS answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL COMMENT '问题ID',
    answerer VARCHAR(100) NOT NULL COMMENT '回答者',
    content TEXT NOT NULL COMMENT '回答内容',
    likes INT DEFAULT 0 COMMENT '点赞数',
    is_best TINYINT(1) DEFAULT 0 COMMENT '是否最佳答案：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '回答时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_question_id (question_id),
    INDEX idx_is_best (is_best),
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建回答点赞记录表（防止重复点赞）
CREATE TABLE IF NOT EXISTS answer_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    answer_id INT NOT NULL COMMENT '回答ID',
    user_identifier VARCHAR(255) NOT NULL COMMENT '用户标识（IP+UA哈希）',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '点赞时间',
    UNIQUE KEY uk_answer_user (answer_id, user_identifier),
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建打印模板表
CREATE TABLE IF NOT EXISTS print_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '模板名称',
    header_text VARCHAR(255) DEFAULT '' COMMENT '页眉文字',
    footer_text VARCHAR(255) DEFAULT '' COMMENT '页脚文字',
    logo_url VARCHAR(500) DEFAULT '' COMMENT 'Logo URL',
    style_json TEXT DEFAULT NULL COMMENT '样式配置JSON',
    is_default TINYINT(1) DEFAULT 0 COMMENT '是否默认模板：0否，1是',
    template_type ENUM('minimal', 'official', 'card') DEFAULT 'minimal' COMMENT '模板类型：minimal极简风, official正式公文, card卡片风',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_name (name),
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入默认打印模板
INSERT INTO print_templates (name, header_text, footer_text, logo_url, style_json, is_default, template_type) VALUES
('极简风', '公告信息管理系统', '© 2024 公告信息管理系统. All rights reserved.', '', '{"fontFamily":"Noto Sans SC, sans-serif","fontSize":"14px","textColor":"#333","backgroundColor":"#fff","borderColor":"#e5e7eb","primaryColor":"#3b82f6","headerBgColor":"#f9fafb","footerBgColor":"#f9fafb"}', 1, 'minimal'),
('正式公文', '公 告', '发布单位：公告信息管理系统', '', '{"fontFamily":"SimSun, Noto Sans SC, sans-serif","fontSize":"16px","textColor":"#111","backgroundColor":"#fff","borderColor":"#333","primaryColor":"#1e40af","headerBgColor":"#fff","footerBgColor":"#fff","titleFontSize":"28px","titleAlign":"center","borderStyle":"double"}', 0, 'official'),
('卡片风', '', '感谢您的关注', '', '{"fontFamily":"Noto Sans SC, sans-serif","fontSize":"14px","textColor":"#374151","backgroundColor":"#f3f4f6","cardBgColor":"#fff","borderRadius":"12px","borderColor":"#e5e7eb","primaryColor":"#8b5cf6","shadow":"0 4px 6px -1px rgba(0,0,0,0.1)","padding":"24px"}', 0, 'card');

-- 创建备份记录表
CREATE TABLE IF NOT EXISTS backup_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL COMMENT '实际文件名',
    display_name VARCHAR(255) NOT NULL COMMENT '显示名称',
    file_size BIGINT NOT NULL DEFAULT 0 COMMENT '文件大小(字节)',
    backup_type ENUM('manual', 'auto_pre_restore') NOT NULL DEFAULT 'manual' COMMENT '备份类型：manual手动, auto_pre_restore恢复前自动备份',
    remark TEXT DEFAULT NULL COMMENT '备注',
    status ENUM('success', 'failed', 'processing') NOT NULL DEFAULT 'success' COMMENT '状态',
    error_log TEXT DEFAULT NULL COMMENT '错误日志',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_created_at (created_at),
    INDEX idx_backup_type (backup_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='数据备份记录表';

-- 创建订阅表
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL COMMENT '订阅人邮箱',
    sub_type ENUM('category', 'author', 'keyword', 'priority') NOT NULL COMMENT '订阅类型：分类/作者/关键词/优先级',
    sub_value VARCHAR(255) NOT NULL COMMENT '订阅值',
    is_paused TINYINT(1) DEFAULT 0 COMMENT '是否暂停：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_email (email),
    INDEX idx_sub_type (sub_type),
    INDEX idx_is_paused (is_paused)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告订阅表';

-- 创建推送记录表
CREATE TABLE IF NOT EXISTS push_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL COMMENT '订阅ID',
    notice_id INT NOT NULL COMMENT '命中公告ID',
    summary VARCHAR(500) NOT NULL COMMENT '推送内容摘要',
    push_status ENUM('generated', 'sent', 'failed') DEFAULT 'generated' COMMENT '推送状态：generated已生成，sent已发送，failed失败',
    pushed_at DATETIME DEFAULT NULL COMMENT '推送时间',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_subscription_id (subscription_id),
    INDEX idx_notice_id (notice_id),
    INDEX idx_push_status (push_status),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='推送记录表';

-- 创建问卷表
CREATE TABLE IF NOT EXISTS surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL COMMENT '问卷标题',
    description TEXT DEFAULT NULL COMMENT '问卷描述',
    start_time DATETIME DEFAULT NULL COMMENT '开始时间',
    end_time DATETIME DEFAULT NULL COMMENT '结束时间',
    is_enabled TINYINT(1) DEFAULT 1 COMMENT '是否启用：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_is_enabled (is_enabled),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷表';

-- 创建问卷问题表
CREATE TABLE IF NOT EXISTS survey_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL COMMENT '问卷ID',
    question_text TEXT NOT NULL COMMENT '问题内容',
    question_type ENUM('single', 'multiple', 'text') NOT NULL COMMENT '题型：single单选，multiple多选，text简答',
    sort_order INT DEFAULT 0 COMMENT '排序序号',
    is_required TINYINT(1) DEFAULT 1 COMMENT '是否必填：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_survey_id (survey_id),
    INDEX idx_sort_order (sort_order),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷问题表';

-- 创建问卷选项表
CREATE TABLE IF NOT EXISTS survey_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL COMMENT '问题ID',
    option_text VARCHAR(500) NOT NULL COMMENT '选项内容',
    sort_order INT DEFAULT 0 COMMENT '排序序号',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_question_id (question_id),
    INDEX idx_sort_order (sort_order),
    FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷选项表';

-- 创建问卷答案表
CREATE TABLE IF NOT EXISTS survey_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_id INT NOT NULL COMMENT '问卷ID',
    question_id INT NOT NULL COMMENT '问题ID',
    visitor_id VARCHAR(255) NOT NULL COMMENT '访客标识（IP+UA哈希）',
    option_id INT DEFAULT NULL COMMENT '选项ID（选择题使用）',
    answer_text TEXT DEFAULT NULL COMMENT '回答内容（简答题使用）',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    INDEX idx_survey_id (survey_id),
    INDEX idx_question_id (question_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_created_at (created_at),
    UNIQUE KEY uk_survey_visitor_question (survey_id, visitor_id, question_id),
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES survey_questions(id) ON DELETE CASCADE,
    FOREIGN KEY (option_id) REFERENCES survey_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='问卷答案表';

-- 创建聊天房间表
CREATE TABLE IF NOT EXISTS chat_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '房间名称',
    description VARCHAR(500) DEFAULT '' COMMENT '房间描述',
    is_default TINYINT(1) DEFAULT 0 COMMENT '是否默认公共房间：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_is_default (is_default)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天房间表';

-- 插入默认公共房间
INSERT INTO chat_rooms (name, description, is_default) VALUES
('公共答疑大厅', '系统默认公共答疑房间，所有人进入后自动加入', 1);

-- 创建聊天消息表
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL COMMENT '房间ID',
    nickname VARCHAR(100) NOT NULL COMMENT '发送者昵称',
    user_identifier VARCHAR(255) NOT NULL COMMENT '发送者标识（IP+UA哈希）',
    message_type ENUM('text', 'image', 'system') NOT NULL DEFAULT 'text' COMMENT '消息类型：text文本，image图片，system系统消息',
    content TEXT NOT NULL COMMENT '消息内容（文本/图片URL/系统消息）',
    mention_nickname VARCHAR(100) DEFAULT NULL COMMENT '@提及的昵称',
    is_admin TINYINT(1) DEFAULT 0 COMMENT '是否管理员消息：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '发送时间',
    INDEX idx_room_id (room_id),
    INDEX idx_created_at (created_at),
    INDEX idx_room_created (room_id, id),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天消息表';

-- 创建聊天在线用户表
CREATE TABLE IF NOT EXISTS chat_users_online (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL COMMENT '房间ID',
    nickname VARCHAR(100) NOT NULL COMMENT '用户昵称',
    user_identifier VARCHAR(255) NOT NULL COMMENT '用户标识（IP+UA哈希）',
    is_admin TINYINT(1) DEFAULT 0 COMMENT '是否管理员：0否，1是',
    last_heartbeat DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后心跳时间',
    joined_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '加入时间',
    UNIQUE KEY uk_room_user (room_id, user_identifier),
    INDEX idx_room_id (room_id),
    INDEX idx_last_heartbeat (last_heartbeat),
    FOREIGN KEY (room_id) REFERENCES chat_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='聊天在线用户表';

-- 创建公告评分表
CREATE TABLE IF NOT EXISTS notice_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notice_id INT NOT NULL COMMENT '公告ID',
    visitor_id VARCHAR(255) NOT NULL COMMENT '访客标识（IP+UA哈希）',
    score TINYINT(1) NOT NULL COMMENT '评分1-5',
    comment TEXT DEFAULT NULL COMMENT '文字评价',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '提交时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    UNIQUE KEY uk_notice_visitor (notice_id, visitor_id),
    INDEX idx_notice_id (notice_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_score (score),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='公告评分评价表';

-- 创建抽奖活动表
CREATE TABLE IF NOT EXISTS lotteries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '活动名称',
    notice_id INT DEFAULT NULL COMMENT '关联公告ID',
    prizes JSON NOT NULL COMMENT '奖品列表JSON，格式：[{"name":"奖品名","count":数量},...]',
    start_time DATETIME NOT NULL COMMENT '参与开始时间',
    end_time DATETIME NOT NULL COMMENT '参与结束时间',
    draw_time DATETIME NOT NULL COMMENT '开奖时间',
    condition_text TEXT DEFAULT NULL COMMENT '参与条件描述',
    status ENUM('draft', 'active', 'paused', 'finished') DEFAULT 'draft' COMMENT '状态：draft草稿，active进行中，paused已暂停，finished已开奖',
    is_drawn TINYINT(1) DEFAULT 0 COMMENT '是否已开奖：0否，1是',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    INDEX idx_status (status),
    INDEX idx_notice_id (notice_id),
    INDEX idx_start_time (start_time),
    INDEX idx_draw_time (draw_time),
    INDEX idx_is_drawn (is_drawn),
    FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='抽奖活动表';

-- 创建抽奖参与表
CREATE TABLE IF NOT EXISTS lottery_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lottery_id INT NOT NULL COMMENT '活动ID',
    visitor_id VARCHAR(255) NOT NULL COMMENT '访客标识（IP+UA哈希）',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '参与时间',
    UNIQUE KEY uk_lottery_visitor (lottery_id, visitor_id),
    INDEX idx_lottery_id (lottery_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='抽奖参与表';

-- 创建抽奖中奖表
CREATE TABLE IF NOT EXISTS lottery_winners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lottery_id INT NOT NULL COMMENT '活动ID',
    prize_name VARCHAR(255) NOT NULL COMMENT '奖品名',
    visitor_id VARCHAR(255) NOT NULL COMMENT '中奖访客标识',
    drawn_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '开奖时间',
    INDEX idx_lottery_id (lottery_id),
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_drawn_at (drawn_at),
    FOREIGN KEY (lottery_id) REFERENCES lotteries(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='抽奖中奖表';
