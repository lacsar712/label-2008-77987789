<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensureQATables();
ensureSurveyTables();

$notice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($notice_id <= 0) {
    header("Location: search_notice.php");
    exit();
}

$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notice) {
    closeConnection($conn);
    header("Location: search_notice.php");
    exit();
}

$update_stmt = $conn->prepare("UPDATE notices SET views = views + 1 WHERE id = ?");
$update_stmt->bind_param("i", $notice_id);
$update_stmt->execute();
$update_stmt->close();

$has_survey = !empty($notice['survey_id']);
closeConnection($conn);

$priority_class = 'priority-' . $notice['priority'];
$priority_text = ['high' => '高', 'medium' => '中', 'low' => '低'][$notice['priority']];
$status_text = ['published' => '已发布', 'draft' => '草稿'][$notice['status']];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($notice['title']); ?> - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .detail-container {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        .detail-header {
            padding: var(--spacing-2xl);
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }
        .detail-header h1 {
            font-size: 1.75rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            line-height: 1.4;
            padding-right: 180px;
        }
        .detail-actions {
            position: absolute;
            top: var(--spacing-2xl);
            right: var(--spacing-2xl);
            display: flex;
            gap: var(--spacing-sm);
            z-index: 10;
        }
        .detail-action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-tertiary);
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .detail-action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: rgba(59,130,246,0.05);
        }
        .detail-action-btn svg {
            width: 16px;
            height: 16px;
        }
        .detail-action-btn.primary {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        .detail-action-btn.primary:hover {
            opacity: 0.9;
            background: var(--gradient-primary);
        }
        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            align-items: center;
        }
        .detail-meta-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--text-muted);
            font-size: 0.875rem;
        }
        .detail-meta-item svg {
            width: 16px;
            height: 16px;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border-color);
            padding: 0 var(--spacing-2xl);
            background: var(--bg-tertiary);
        }
        .tab-item {
            padding: var(--spacing-md) var(--spacing-lg);
            color: var(--text-secondary);
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        .tab-item:hover {
            color: var(--text-primary);
        }
        .tab-item.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
            padding: var(--spacing-2xl);
        }
        .tab-content.active {
            display: block;
        }
        .detail-body {
            color: var(--text-secondary);
            line-height: 1.8;
            font-size: 1rem;
        }
        .detail-body p {
            margin-bottom: var(--spacing-md);
        }
        .qa-section {
            max-width: 900px;
            margin: 0 auto;
        }
        .ask-form {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
        }
        .ask-form h3 {
            font-size: 1.125rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
        }
        .ask-form-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }
        .ask-form-row input,
        .ask-form-row textarea {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-family: inherit;
        }
        .ask-form-row textarea {
            resize: vertical;
            min-height: 80px;
        }
        .question-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .question-item:hover {
            border-color: var(--primary-color);
        }
        .question-header {
            padding: var(--spacing-lg);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: var(--spacing-md);
        }
        .question-main {
            flex: 1;
        }
        .question-content {
            color: var(--text-primary);
            font-size: 1rem;
            margin-bottom: var(--spacing-sm);
            line-height: 1.6;
        }
        .question-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .question-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .question-meta-item svg {
            width: 14px;
            height: 14px;
        }
        .qa-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        .qa-status-badge.status-open {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
        }
        .qa-status-badge.status-resolved {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .expand-icon {
            flex-shrink: 0;
            color: var(--text-muted);
            transition: transform 0.3s ease;
        }
        .expand-icon svg {
            width: 20px;
            height: 20px;
        }
        .question-item.expanded .expand-icon {
            transform: rotate(180deg);
        }
        .question-body {
            display: none;
            border-top: 1px solid var(--border-color);
            padding: var(--spacing-lg);
        }
        .question-item.expanded .question-body {
            display: block;
        }
        .answers-list {
            margin-bottom: var(--spacing-lg);
        }
        .answer-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            position: relative;
        }
        .answer-item.is-best {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }
        .answer-best-tag {
            position: absolute;
            top: var(--spacing-sm);
            right: var(--spacing-sm);
            background: var(--success-color);
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .answer-best-tag svg {
            width: 12px;
            height: 12px;
        }
        .answer-content {
            color: var(--text-secondary);
            line-height: 1.7;
            margin-bottom: var(--spacing-sm);
            padding-right: 80px;
        }
        .answer-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .answer-meta {
            display: flex;
            gap: var(--spacing-md);
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .answer-actions {
            display: flex;
            gap: var(--spacing-sm);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 0.3rem 0.6rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            color: var(--text-secondary);
            font-size: 0.8125rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .action-btn.liked {
            background: rgba(239, 68, 68, 0.1);
            border-color: var(--error-color);
            color: var(--error-color);
        }
        .action-btn.best {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
        }
        .action-btn svg {
            width: 14px;
            height: 14px;
        }
        .answer-form {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
        }
        .answer-form-row {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: var(--spacing-sm);
            margin-bottom: var(--spacing-sm);
        }
        .answer-form-row input,
        .answer-form-row textarea {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-family: inherit;
        }
        .answer-form-row textarea {
            resize: vertical;
            min-height: 60px;
        }
        .empty-qa {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .empty-qa svg {
            width: 64px;
            height: 64px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9375rem;
            margin-bottom: var(--spacing-lg);
            transition: all 0.3s ease;
        }
        .back-link:hover {
            color: var(--primary-light);
            transform: translateX(-3px);
        }
        .back-link svg {
            width: 18px;
            height: 18px;
        }
        .qa-pagination {
            display: flex;
            justify-content: center;
            gap: var(--spacing-sm);
            margin-top: var(--spacing-lg);
        }
        .qa-page-btn {
            padding: var(--spacing-sm) var(--spacing-md);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .qa-page-btn:hover:not(:disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        .qa-page-btn.active {
            background: var(--gradient-primary);
            color: white;
            border-color: transparent;
        }
        .qa-page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .survey-section {
            max-width: 900px;
            margin: 0 auto;
        }
        .survey-container {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: var(--spacing-xl);
        }
        .survey-header {
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-lg);
            border-bottom: 1px solid var(--border-color);
        }
        .survey-title {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
            font-weight: 600;
        }
        .survey-description {
            color: var(--text-secondary);
            font-size: 0.9375rem;
            margin-bottom: var(--spacing-md);
            line-height: 1.7;
        }
        .survey-meta {
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-md);
            font-size: 0.8125rem;
            color: var(--text-muted);
        }
        .survey-meta-item {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .survey-meta-item svg {
            width: 14px;
            height: 14px;
        }
        .survey-question {
            margin-bottom: var(--spacing-xl);
            padding-bottom: var(--spacing-xl);
            border-bottom: 1px solid var(--border-color);
        }
        .survey-question:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        .question-text {
            font-size: 1rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-md);
            font-weight: 500;
            line-height: 1.6;
        }
        .question-text .required {
            color: var(--error-color);
            margin-left: 4px;
        }
        .question-type-badge {
            display: inline-block;
            padding: 0.15rem 0.5rem;
            border-radius: var(--radius-sm);
            font-size: 0.7rem;
            font-weight: 500;
            margin-right: var(--spacing-sm);
            vertical-align: middle;
        }
        .question-type-badge.single {
            background: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        .question-type-badge.multiple {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }
        .question-type-badge.text {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
        }
        .options-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-sm);
        }
        .option-item {
            display: flex;
            align-items: flex-start;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .option-item:hover {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.05);
        }
        .option-item.selected {
            border-color: var(--primary-color);
            background: rgba(99, 102, 241, 0.1);
        }
        .option-item input[type="radio"],
        .option-item input[type="checkbox"] {
            margin-top: 3px;
            flex-shrink: 0;
            accent-color: var(--primary-color);
        }
        .option-text {
            color: var(--text-secondary);
            font-size: 0.9375rem;
            line-height: 1.5;
            flex: 1;
        }
        .text-answer-input {
            width: 100%;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            font-family: inherit;
            resize: vertical;
            min-height: 100px;
            transition: all 0.2s ease;
        }
        .text-answer-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .survey-submit-area {
            margin-top: var(--spacing-xl);
            text-align: center;
        }
        .survey-submit-area .btn {
            min-width: 150px;
        }
        .survey-success {
            text-align: center;
            padding: var(--spacing-2xl);
        }
        .survey-success-icon {
            width: 64px;
            height: 64px;
            color: var(--success-color);
            margin-bottom: var(--spacing-md);
        }
        .survey-success h3 {
            font-size: 1.25rem;
            color: var(--text-primary);
            margin-bottom: var(--spacing-sm);
        }
        .survey-success p {
            color: var(--text-muted);
            font-size: 0.9375rem;
        }
        .survey-disabled {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .survey-disabled svg {
            width: 48px;
            height: 48px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        .survey-empty {
            text-align: center;
            padding: var(--spacing-2xl);
            color: var(--text-muted);
        }
        .survey-empty svg {
            width: 48px;
            height: 48px;
            margin-bottom: var(--spacing-md);
            opacity: 0.5;
        }
        @media (max-width: 768px) {
            .ask-form-row,
            .answer-form-row {
                grid-template-columns: 1fr;
            }
            .detail-header,
            .tab-content {
                padding: var(--spacing-lg);
            }
            .detail-header h1 {
                padding-right: 0;
                font-size: 1.375rem;
                margin-top: 52px;
            }
            .detail-actions {
                top: var(--spacing-md);
                right: var(--spacing-md);
            }
            .detail-action-btn {
                padding: 6px 10px;
                font-size: 0.8125rem;
            }
            .detail-action-btn span {
                display: none;
            }
            .detail-action-btn svg {
                width: 18px;
                height: 18px;
            }
            .survey-container {
                padding: var(--spacing-lg);
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <svg class="logo-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 7H17M7 12H17M7 17H13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <h1>公告信息管理系统</h1>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php">首页</a></li>
                <li><a href="add_notice.php">添加公告</a></li>
                <li><a href="search_notice.php">查询公告</a></li>
                <li><a href="qa_center.php">问答中心</a></li>
                <li><a href="chat.php">在线答疑</a></li>
                <li><a href="feedback.php">意见反馈</a></li>
                <li><a href="feedback_query.php">工单查询</a></li>
                <li><a href="feedback_admin.php">反馈管理</a></li>
                <li><a href="survey_admin.php">问卷管理</a></li>
                <li><a href="survey_results.php">问卷结果</a></li>
                <li><a href="print_template_admin.php">打印模板</a></li>
                <li><a href="subscription_admin.php">订阅管理</a></li>
                <li><a href="push_records.php">推送记录</a></li>
                <li><a href="system_backup.php">系统备份</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="main-content">
            <a href="search_notice.php" class="back-link">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                返回公告列表
            </a>

            <div class="detail-container">
                <div class="detail-header">
                    <div class="detail-actions">
                        <a href="print_preview.php?notice_id=<?php echo $notice_id; ?>" target="_blank" class="detail-action-btn" title="打印预览">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><polyline points="6 9 6 2 18 2 18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><rect x="6" y="14" width="12" height="8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>打印</span>
                        </a>
                        <a href="print_preview.php?notice_id=<?php echo $notice_id; ?>" target="_blank" class="detail-action-btn primary" title="下载PDF">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><polyline points="7 10 12 15 17 10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><line x1="12" y1="15" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>PDF</span>
                        </a>
                    </div>
                    <h1><?php echo htmlspecialchars($notice['title']); ?></h1>
                    <div class="detail-meta">
                        <span class="detail-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo htmlspecialchars($notice['author']); ?>
                        </span>
                        <span class="detail-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo date('Y-m-d H:i', strtotime($notice['publish_date'])); ?>
                        </span>
                        <span class="priority-badge <?php echo $priority_class; ?>"><?php echo $priority_text; ?></span>
                        <span class="qa-status-badge status-<?php echo $notice['status']; ?>"><?php echo $status_text; ?></span>
                        <span class="detail-meta-item">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M15 12C15 13.6569 13.6569 15 12 15C10.3431 15 9 13.6569 9 12C9 10.3431 10.3431 9 12 9C13.6569 9 15 10.3431 15 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2.45801 12C3.73201 7.943 7.52301 5 12.001 5C16.478 5 20.269 7.943 21.543 12C20.269 16.057 16.478 19 12.001 19C7.52301 19 3.73201 16.057 2.45801 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <?php echo $notice['views']; ?> 浏览
                        </span>
                    </div>
                </div>

                <div class="tabs">
                    <div class="tab-item active" data-tab="content">公告内容</div>
                    <div class="tab-item" data-tab="qa">问答讨论</div>
                    <?php if ($has_survey): ?>
                        <div class="tab-item" data-tab="survey">问卷调查</div>
                    <?php endif; ?>
                </div>

                <div class="tab-content active" id="tab-content">
                    <div class="detail-body">
                        <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                    </div>
                </div>

                <div class="tab-content" id="tab-qa">
                    <div class="qa-section">
                        <div class="ask-form">
                            <h3>我要提问</h3>
                            <div class="ask-form-row">
                                <input type="text" id="askerName" placeholder="您的称呼">
                                <textarea id="questionContent" placeholder="请输入您的问题..."></textarea>
                            </div>
                            <div style="text-align: right;">
                                <button class="btn btn-primary" id="submitQuestionBtn">
                                    <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 19V5M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    提交问题
                                </button>
                            </div>
                        </div>

                        <div id="questionsList"></div>

                        <div class="qa-pagination" id="qaPagination"></div>
                    </div>
                </div>

                <?php if ($has_survey): ?>
                    <div class="tab-content" id="tab-survey">
                        <div class="survey-section">
                            <div id="surveyContainer"></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 公告信息管理系统. All rights reserved.</p>
        </div>
    </footer>

    <script>
    (function() {
        var NOTICE_ID = <?php echo $notice_id; ?>;
        var SURVEY_ID = <?php echo intval($notice['survey_id'] ?? 0); ?>;
        var HAS_SURVEY = <?php echo $has_survey ? 'true' : 'false'; ?>;
        var currentPage = 1;
        var totalPages = 1;
        var loadedAnswers = {};
        var surveyData = null;
        var surveyAnswers = {};
        var isSurveySubmitted = false;

        var tabItems = document.querySelectorAll('.tab-item');
        tabItems.forEach(function(tab) {
            tab.addEventListener('click', function() {
                var target = tab.dataset.tab;
                tabItems.forEach(function(t) { t.classList.remove('active'); });
                tab.classList.add('active');
                document.querySelectorAll('.tab-content').forEach(function(c) { c.classList.remove('active'); });
                document.getElementById('tab-' + target).classList.add('active');
                if (target === 'qa' && !window.qaLoaded) {
                    loadQuestions();
                    window.qaLoaded = true;
                }
                if (target === 'survey' && HAS_SURVEY && !window.surveyLoaded) {
                    loadSurvey();
                    window.surveyLoaded = true;
                }
            });
        });

        document.getElementById('submitQuestionBtn').addEventListener('click', submitQuestion);

        function loadSurvey() {
            var container = document.getElementById('surveyContainer');
            container.innerHTML = '<div class="loading"><div class="loading-spinner"></div><p>加载问卷中...</p></div>';

            fetch('api_survey_submit.php?action=check_submitted&survey_id=' + SURVEY_ID)
                .then(function(res) { return res.json(); })
                .then(function(checkData) {
                    if (checkData.success && checkData.data.submitted) {
                        isSurveySubmitted = true;
                        renderSurveySubmitted();
                        return;
                    }
                    return fetch('api_survey_submit.php?action=detail_for_answer&survey_id=' + SURVEY_ID)
                        .then(function(res) { return res.json(); })
                        .then(function(data) {
                            if (data.success) {
                                surveyData = data.data;
                                renderSurveyForm();
                            } else {
                                renderSurveyError(data.message || '问卷加载失败');
                            }
                        });
                })
                .catch(function() {
                    renderSurveyError('网络错误，请稍后重试');
                });
        }

        function renderSurveyError(message) {
            var container = document.getElementById('surveyContainer');
            container.innerHTML = '<div class="survey-disabled"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 9V13M12 17H12.01M10.29 3.86L1.82 18C1.58 18.42 1.58 18.94 1.82 19.36C2.06 19.78 2.52 20.03 3 20.03H21C21.48 20.03 21.94 19.78 22.18 19.36C22.42 18.94 22.42 18.42 22.18 18L13.71 3.86C13.47 3.44 13.01 3.19 12.53 3.19C12.05 3.19 11.59 3.44 11.35 3.86Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>' + message + '</p></div>';
        }

        function renderSurveySubmitted() {
            var container = document.getElementById('surveyContainer');
            container.innerHTML = '<div class="survey-success"><svg class="survey-success-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><h3>感谢您的参与！</h3><p>您已经提交过该问卷，每位用户仅能提交一次。</p></div>';
        }

        function renderSurveyForm() {
            var container = document.getElementById('surveyContainer');
            var survey = surveyData.survey;
            var questions = surveyData.questions;

            if (!survey.is_enabled) {
                container.innerHTML = '<div class="survey-disabled"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 15V17M12 7V13M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>该问卷已关闭，无法作答。</p></div>';
                return;
            }

            var now = new Date();
            if (survey.start_time && new Date(survey.start_time) > now) {
                container.innerHTML = '<div class="survey-disabled"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>该问卷尚未开始，开始时间：' + survey.start_time + '</p></div>';
                return;
            }
            if (survey.end_time && new Date(survey.end_time) < now) {
                container.innerHTML = '<div class="survey-disabled"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>该问卷已结束，结束时间：' + survey.end_time + '</p></div>';
                return;
            }

            if (!questions || questions.length === 0) {
                container.innerHTML = '<div class="survey-empty"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5M12 12H12.01M12 16H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>该问卷暂无题目</p></div>';
                return;
            }

            var html = '<div class="survey-container">';
            html += '  <div class="survey-header">';
            html += '    <h2 class="survey-title">' + htmlEscape(survey.title) + '</h2>';
            if (survey.description) {
                html += '    <p class="survey-description">' + htmlEscape(survey.description) + '</p>';
            }
            html += '    <div class="survey-meta">';
            html += '      <span class="survey-meta-item">';
            html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 5H7C5.89543 5 5 5.89543 5 7V19C5 20.1046 5.89543 21 7 21H17C18.1046 21 19 20.1046 19 19V7C19 5.89543 18.1046 5 17 5H15M9 5C9 6.10457 9.89543 7 11 7H13C14.1046 7 15 6.10457 15 5M9 5C9 3.89543 9.89543 3 11 3H13C14.1046 3 15 3.89543 15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            html += '        共 ' + questions.length + ' 题';
            html += '      </span>';
            if (survey.start_time) {
                html += '      <span class="survey-meta-item">';
                html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '        ' + survey.start_time + ' 至 ' + (survey.end_time || '长期有效');
                html += '      </span>';
            }
            html += '    </div>';
            html += '  </div>';

            html += '  <div id="surveyQuestions">';
            questions.forEach(function(q, qIndex) {
                html += '  <div class="survey-question" data-question-id="' + q.id + '">';
                var typeText = q.question_type === 'single' ? '单选' : (q.question_type === 'multiple' ? '多选' : '简答');
                var typeClass = q.question_type;
                html += '    <div class="question-text">';
                html += '      <span class="question-type-badge ' + typeClass + '">' + typeText + '</span>';
                html += '      <span>' + (qIndex + 1) + '. ' + htmlEscape(q.question_text) + '</span>';
                if (q.is_required) {
                    html += '      <span class="required">*</span>';
                }
                html += '    </div>';

                if (q.question_type === 'single' || q.question_type === 'multiple') {
                    html += '    <div class="options-list">';
                    if (q.options) {
                        q.options.forEach(function(opt) {
                            var inputType = q.question_type === 'single' ? 'radio' : 'checkbox';
                            html += '    <label class="option-item" data-option-id="' + opt.id + '">';
                            html += '      <input type="' + inputType + '" name="question_' + q.id + '" value="' + opt.id + '" onchange="handleOptionChange(' + q.id + ', ' + opt.id + ', this)">';
                            html += '      <span class="option-text">' + htmlEscape(opt.option_text) + '</span>';
                            html += '    </label>';
                        });
                    }
                    html += '    </div>';
                } else {
                    html += '    <textarea class="text-answer-input" id="text_answer_' + q.id + '" placeholder="请输入您的回答..." onchange="handleTextChange(' + q.id + ', this)"></textarea>';
                }
                html += '  </div>';
            });
            html += '  </div>';

            html += '  <div class="survey-submit-area">';
            html += '    <button class="btn btn-primary" id="submitSurveyBtn" onclick="submitSurvey()">';
            html += '      <svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            html += '      提交问卷';
            html += '    </button>';
            html += '  </div>';
            html += '</div>';

            container.innerHTML = html;

            document.querySelectorAll('.option-item').forEach(function(item) {
                item.addEventListener('click', function(e) {
                    if (e.target.tagName !== 'INPUT') {
                        var input = item.querySelector('input');
                        input.checked = !input.checked;
                        input.dispatchEvent(new Event('change'));
                    }
                });
            });
        }

        window.handleOptionChange = function(questionId, optionId, input) {
            var questionItem = document.querySelector('.survey-question[data-question-id="' + questionId + '"]');
            if (input.type === 'radio') {
                questionItem.querySelectorAll('.option-item').forEach(function(item) {
                    item.classList.remove('selected');
                });
                if (input.checked) {
                    input.closest('.option-item').classList.add('selected');
                    surveyAnswers[questionId] = [optionId];
                }
            } else {
                input.closest('.option-item').classList.toggle('selected', input.checked);
                if (!surveyAnswers[questionId]) {
                    surveyAnswers[questionId] = [];
                }
                var idx = surveyAnswers[questionId].indexOf(optionId);
                if (input.checked && idx === -1) {
                    surveyAnswers[questionId].push(optionId);
                } else if (!input.checked && idx !== -1) {
                    surveyAnswers[questionId].splice(idx, 1);
                }
            }
        };

        window.handleTextChange = function(questionId, textarea) {
            surveyAnswers[questionId] = textarea.value.trim();
        };

        window.submitSurvey = function() {
            if (isSurveySubmitted) {
                alert('您已经提交过该问卷了');
                return;
            }

            var questions = surveyData.questions;
            var answers = [];

            for (var i = 0; i < questions.length; i++) {
                var q = questions[i];
                var answer = surveyAnswers[q.id];

                if (q.is_required) {
                    if (!answer || (Array.isArray(answer) && answer.length === 0) || (typeof answer === 'string' && answer === '')) {
                        alert('请完成第 ' + (i + 1) + ' 题：' + q.question_text);
                        return;
                    }
                }

                if (answer && (Array.isArray(answer) ? answer.length > 0 : answer !== '')) {
                    if (q.question_type === 'text') {
                        answers.push({
                            question_id: q.id,
                            answer_text: answer
                        });
                    } else {
                        var optionIds = Array.isArray(answer) ? answer : [answer];
                        optionIds.forEach(function(optId) {
                            answers.push({
                                question_id: q.id,
                                option_id: optId
                            });
                        });
                    }
                }
            }

            if (answers.length === 0) {
                alert('请至少回答一个问题');
                return;
            }

            var btn = document.getElementById('submitSurveyBtn');
            btn.disabled = true;
            btn.innerHTML = '<div class="loading-spinner" style="width:20px;height:20px;border-width:2px;margin:0;"></div> 提交中...';

            fetch('api_survey_submit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    survey_id: SURVEY_ID,
                    answers: answers
                })
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    isSurveySubmitted = true;
                    renderSurveySubmitted();
                } else {
                    alert(data.message || '提交失败');
                    btn.disabled = false;
                    btn.innerHTML = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 提交问卷';
                }
            }).catch(function() {
                alert('网络错误，请稍后重试');
                btn.disabled = false;
                btn.innerHTML = '<svg class="btn-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 12H19M12 5L19 12L12 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg> 提交问卷';
            });
        };

        function htmlEscape(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function loadQuestions(page) {
            page = page || 1;
            currentPage = page;
            fetch('api_qa_questions.php?notice_id=' + NOTICE_ID + '&page=' + page + '&per_page=5')
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        renderQuestions(data.data.list);
                        renderPagination(data.data.pagination);
                    }
                });
        }

        function renderQuestions(list) {
            var container = document.getElementById('questionsList');
            if (!list || list.length === 0) {
                container.innerHTML = '<div class="empty-qa"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.228 9C8.73983 7.8342 9.96676 7 11.4 7C13.3875 7 15 8.79086 15 11C15 12.6569 13.6569 14 12 14C11.2817 14 10.6279 13.7895 10.0858 13.4202L9 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg><p>暂无问题，快来第一个提问吧！</p></div>';
                return;
            }
            var html = '';
            list.forEach(function(q) {
                html += '<div class="question-item" data-id="' + q.id + '" data-asker="' + q.asker + '">';
                html += '  <div class="question-header" onclick="toggleQuestion(' + q.id + ')">';
                html += '    <div class="question-main">';
                html += '      <div class="question-content">' + q.content + '</div>';
                html += '      <div class="question-meta">';
                html += '        <span class="question-meta-item">';
                html += '          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16 7C16 9.20914 14.2091 11 12 11C9.79086 11 8 9.20914 8 7C8 4.79086 9.79086 3 12 3C14.2091 3 16 4.79086 16 7Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M12 14C8.13401 14 5 17.134 5 21H19C19 17.134 15.866 14 12 14Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '          ' + q.asker;
                html += '        </span>';
                html += '        <span class="question-meta-item">';
                html += '          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 8V12L15 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '          ' + q.created_at.substring(0, 16);
                html += '        </span>';
                html += '        <span class="question-meta-item">';
                html += '          <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                html += '          ' + q.answer_count + ' 回答';
                html += '        </span>';
                html += '      </div>';
                html += '    </div>';
                html += '    <span class="qa-status-badge status-' + q.status + '">' + (q.status === 'resolved' ? '已解答' : '待解答') + '</span>';
                html += '    <span class="expand-icon"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M6 9L12 15L18 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>';
                html += '  </div>';
                html += '  <div class="question-body" id="question-body-' + q.id + '"></div>';
                html += '</div>';
            });
            container.innerHTML = html;
        }

        function renderPagination(pagination) {
            totalPages = pagination.total_pages;
            var container = document.getElementById('qaPagination');
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            var html = '';
            html += '<button class="qa-page-btn" onclick="loadQuestions(' + (currentPage - 1) + ')" ' + (currentPage <= 1 ? 'disabled' : '') + '>上一页</button>';
            var start = Math.max(1, currentPage - 2);
            var end = Math.min(totalPages, currentPage + 2);
            if (start > 1) {
                html += '<button class="qa-page-btn" onclick="loadQuestions(1)">1</button>';
                if (start > 2) html += '<span style="color:var(--text-muted);">...</span>';
            }
            for (var i = start; i <= end; i++) {
                html += '<button class="qa-page-btn ' + (i === currentPage ? 'active' : '') + '" onclick="loadQuestions(' + i + ')">' + i + '</button>';
            }
            if (end < totalPages) {
                if (end < totalPages - 1) html += '<span style="color:var(--text-muted);">...</span>';
                html += '<button class="qa-page-btn" onclick="loadQuestions(' + totalPages + ')">' + totalPages + '</button>';
            }
            html += '<button class="qa-page-btn" onclick="loadQuestions(' + (currentPage + 1) + ')" ' + (currentPage >= totalPages ? 'disabled' : '') + '>下一页</button>';
            container.innerHTML = html;
        }

        window.toggleQuestion = function(questionId) {
            var item = document.querySelector('.question-item[data-id="' + questionId + '"]');
            item.classList.toggle('expanded');
            if (item.classList.contains('expanded') && !loadedAnswers[questionId]) {
                loadAnswers(questionId);
            }
        };

        function loadAnswers(questionId) {
            fetch('api_qa_answers.php?question_id=' + questionId)
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    if (data.success) {
                        loadedAnswers[questionId] = data.data;
                        renderAnswers(questionId, data.data);
                    }
                });
        }

        function renderAnswers(questionId, answers) {
            var container = document.getElementById('question-body-' + questionId);
            var asker = document.querySelector('.question-item[data-id="' + questionId + '"]').dataset.asker;
            var html = '<div class="answers-list" id="answers-list-' + questionId + '">';
            if (!answers || answers.length === 0) {
                html += '<div style="text-align:center;padding:var(--spacing-lg);color:var(--text-muted);font-size:0.875rem;">暂无回答，快来抢沙发！</div>';
            } else {
                answers.forEach(function(a) {
                    html += renderAnswerItem(questionId, a, asker);
                });
            }
            html += '</div>';
            html += '<div class="answer-form">';
            html += '  <div class="answer-form-row">';
            html += '    <input type="text" placeholder="您的称呼" id="answerer-' + questionId + '">';
            html += '    <textarea placeholder="请输入您的回答..." id="answer-content-' + questionId + '"></textarea>';
            html += '  </div>';
            html += '  <div style="text-align:right;">';
            html += '    <button class="btn btn-primary" style="padding:var(--spacing-sm) var(--spacing-lg);font-size:0.875rem;" onclick="submitAnswer(' + questionId + ')">提交回答</button>';
            html += '  </div>';
            html += '</div>';
            container.innerHTML = html;
        }

        function renderAnswerItem(questionId, answer, asker) {
            var html = '<div class="answer-item ' + (answer.is_best ? 'is-best' : '') + '" id="answer-' + answer.id + '">';
            if (answer.is_best) {
                html += '<span class="answer-best-tag"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>最佳</span>';
            }
            html += '  <div class="answer-content">' + answer.content + '</div>';
            html += '  <div class="answer-footer">';
            html += '    <div class="answer-meta">';
            html += '      <span>' + answer.answerer + '</span>';
            html += '      <span>' + answer.created_at.substring(0, 16) + '</span>';
            html += '    </div>';
            html += '    <div class="answer-actions">';
            html += '      <button class="action-btn ' + (answer.liked ? 'liked' : '') + '" onclick="toggleLike(' + answer.id + ', this)">';
            html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M20.84 4.61C20.3293 4.099 19.7228 3.69365 19.0554 3.41708C18.3879 3.14052 17.6725 3 16.95 3C15.7749 3 14.6534 3.46464 13.7917 4.32628L12 6.118L10.2083 4.32628C8.52313 2.64109 5.77687 2.64109 4.09168 4.32628C2.40649 6.01147 2.40649 8.75773 4.09168 10.4429L12 18.3512L19.9083 10.4429C20.4193 9.93224 20.8247 9.32574 21.1012 8.65828C21.3778 7.99081 21.5183 7.27543 21.5 6.553C21.5 5.85074 21.3608 5.15728 21.095 4.509L20.84 4.61Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            html += '        <span class="like-count">' + answer.likes + '</span>';
            html += '      </button>';
            html += '      <button class="action-btn ' + (answer.is_best ? 'best' : '') + '" onclick="setBest(' + questionId + ',' + answer.id + ',' + answer.is_best + ', this)" ' + (answer.answerer !== asker ? 'title="只有提问者可设置"' : '') + '>';
            html += '        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.049 2.92698C11.3483 1.98867 12.6517 1.98867 12.951 2.92698L14.8534 8.87647C14.9863 9.29185 15.3776 9.5747 15.8156 9.5747H22.0523C23.0302 9.5747 23.4352 10.8261 22.6134 11.4267L17.6084 15.0714C17.2471 15.3345 17.096 15.8059 17.2093 16.2457L18.8641 22.6132C19.0971 23.5183 18.0282 24.2426 17.2272 23.7718L12.0461 20.7678C11.6731 20.5516 11.2069 20.5516 10.8339 20.7678L5.65276 23.7718C4.85181 24.2426 3.78285 23.5183 4.0159 22.6132L5.67065 16.2457C5.78396 15.8059 5.63283 15.3345 5.27158 15.0714L0.266548 11.4267C-0.555213 10.8261 -0.150201 9.5747 0.827703 9.5747H7.06439C7.5024 9.5747 7.89368 9.29185 8.02663 8.87647L11.049 2.92698Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            html += '        ' + (answer.is_best ? '已设最佳' : '设为最佳');
            html += '      </button>';
            html += '    </div>';
            html += '  </div>';
            html += '</div>';
            return html;
        }

        function submitQuestion() {
            var asker = document.getElementById('askerName').value.trim();
            var content = document.getElementById('questionContent').value.trim();
            if (!asker) { alert('请填写您的称呼'); return; }
            if (!content) { alert('请填写问题内容'); return; }
            var btn = document.getElementById('submitQuestionBtn');
            btn.disabled = true;
            fetch('api_qa_questions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notice_id: NOTICE_ID, asker: asker, content: content })
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('askerName').value = '';
                    document.getElementById('questionContent').value = '';
                    loadQuestions(1);
                } else {
                    alert(data.message || '提交失败');
                }
            }).finally(function() { btn.disabled = false; });
        }

        window.submitAnswer = function(questionId) {
            var answerer = document.getElementById('answerer-' + questionId).value.trim();
            var content = document.getElementById('answer-content-' + questionId).value.trim();
            if (!answerer) { alert('请填写您的称呼'); return; }
            if (!content) { alert('请填写回答内容'); return; }
            fetch('api_qa_answers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ question_id: questionId, answerer: answerer, content: content })
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    document.getElementById('answerer-' + questionId).value = '';
                    document.getElementById('answer-content-' + questionId).value = '';
                    delete loadedAnswers[questionId];
                    loadAnswers(questionId);
                    loadQuestions(currentPage);
                } else {
                    alert(data.message || '提交失败');
                }
            });
        };

        window.toggleLike = function(answerId, btn) {
            var isLiked = btn.classList.contains('liked');
            var action = isLiked ? 'unlike' : 'like';
            fetch('api_qa_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: action, answer_id: answerId })
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    btn.classList.toggle('liked', data.data.liked);
                    btn.querySelector('.like-count').textContent = data.data.likes;
                } else {
                    alert(data.message || '操作失败');
                }
            });
        };

        window.setBest = function(questionId, answerId, isBest, btn) {
            var operator = prompt('请输入您的称呼（仅提问者可设置最佳答案）：');
            if (!operator) return;
            var action = isBest ? 'unset_best' : 'set_best';
            var payload = { action: action, question_id: questionId, operator: operator };
            if (!isBest) payload.answer_id = answerId;
            fetch('api_qa_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    delete loadedAnswers[questionId];
                    loadAnswers(questionId);
                    loadQuestions(currentPage);
                } else {
                    alert(data.message || '操作失败');
                }
            });
        };
    })();
    </script>
</body>
</html>
