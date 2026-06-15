<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
require_once 'controllers/NoticeControllers.php';
require_once 'views/components/LayoutComponents.php';
require_once 'views/components/NoticeComponents.php';

$conn = getConnection();
$controller = new SearchNoticeController($conn);
$controller->noticeService->ensureIndexes();
$data = $controller->handle();

$service = $controller->noticeService;
$criteria = $data['criteria'];
$result = $data['result'];
$items = $result->items;

$navbar = new NavbarComponent('search_notice.php');
$footer = new FooterComponent();
$searchForm = new SearchFormComponent($criteria);
$pagination = new PaginationComponent($result, $criteria);
$tableRows = new NoticeTableRowComponent($service, $criteria);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>查询公告 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php $navbar->render(); ?>

    <div class="container">
        <div class="main-content">
            <?php AlertComponent::renderSuccess($data['success_message']); ?>
            <?php AlertComponent::renderError($data['error_message']); ?>

            <?php $searchForm->render(); ?>
            <?php $pagination->renderInfo(); ?>
            <?php $tableRows->renderTable($items); ?>
            <?php $pagination->render(); ?>
        </div>
    </div>

    <?php $footer->render(); ?>
    <?php closeConnection($conn); ?>
</body>
</html>
