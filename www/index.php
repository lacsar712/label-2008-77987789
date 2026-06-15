<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
require_once 'controllers/NoticeControllers.php';
require_once 'views/components/LayoutComponents.php';
require_once 'views/components/NoticeComponents.php';

$conn = getConnection();
$controller = new HomeController($conn);
$controller->noticeService->ensureIndexes();
$data = $controller->handle();

$stats = $data['stats'];
$latestNotices = $data['latest_notices'];

$navbar = new NavbarComponent('index.php');
$footer = new FooterComponent();
$cardRenderer = new NoticeCardComponent($controller->noticeService);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php $navbar->render(); ?>

    <div class="container">
        <div class="main-content">
            <div class="welcome-banner">
                <div class="banner-content">
                    <h2>欢迎使用公告信息管理系统</h2>
                    <p>高效管理您的公告信息，让信息传递更加便捷</p>
                </div>
                <div class="banner-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">总公告数</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['today']; ?></div>
                        <div class="stat-label">今日发布</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_views']; ?></div>
                        <div class="stat-label">总浏览量</div>
                    </div>
                </div>
            </div>

            <div class="quick-actions">
                <h3>快捷操作</h3>
                <div class="action-cards">
                    <a href="add_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>添加公告</h4>
                        <p>发布新的公告信息</p>
                    </a>
                    <a href="search_notice.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L15 15M17 10C17 13.866 13.866 17 10 17C6.13401 17 3 13.866 3 10C3 6.13401 6.13401 3 10 3C13.866 3 17 6.13401 17 10Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>查询公告</h4>
                        <p>搜索和浏览公告</p>
                    </a>
                    <a href="notice_calendar.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8 7V3M16 7V3M7 11H17M5 21H19C20.1046 21 21 20.1046 21 19V7C21 5.89543 20.1046 5 19 5H5C3.89543 5 3 5.89543 3 7V19C3 20.1046 3.89543 21 5 21Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>公告日历</h4>
                        <p>日历视图浏览公告</p>
                    </a>
                    <a href="qa_center.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.228 9C8.73983 7.8342 9.96676 7 11.4 7C13.3875 7 15 8.79086 15 11C15 12.6569 13.6569 14 12 14C11.2817 14 10.6279 13.7895 10.0858 13.4202L9 14M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>问答中心</h4>
                        <p>浏览全站问答互动</p>
                    </a>
                    <a href="chat.php" class="action-card">
                        <svg class="action-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15C21 15.5304 20.7893 16.0391 20.4142 16.4142C20.0391 16.7893 19.5304 17 19 17H7L3 21V5C3 4.46957 3.21071 3.96086 3.58579 3.58579C3.96086 3.21071 4.46957 3 5 3H19C19.5304 3 20.0391 3.21071 20.4142 3.58579C20.7893 3.96086 21 4.46957 21 5V15Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <h4>在线答疑</h4>
                        <p>实时在线交流答疑</p>
                    </a>
                </div>
            </div>

            <div class="recent-notices">
                <div class="section-header">
                    <h3>最新公告</h3>
                    <a href="search_notice.php" class="view-all">查看全部 →</a>
                </div>
                <div class="notices-grid">
                    <?php $cardRenderer->renderGrid($latestNotices); ?>
                </div>
            </div>
        </div>
    </div>

    <?php $footer->render(); ?>
    <?php closeConnection($conn); ?>
</body>
</html>
