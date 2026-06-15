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
    priority ENUM('high', 'medium', 'low') DEFAULT 'medium' COMMENT '优先级',
    views INT DEFAULT 0 COMMENT '浏览次数',
    INDEX idx_publish_date (publish_date),
    INDEX idx_status (status),
    INDEX idx_category (category)
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
