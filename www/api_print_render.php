<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';
ensurePrintTemplates();

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

if ($notice_id <= 0) {
    jsonResponse(['success' => false, 'message' => '无效的公告ID']);
}

$conn = getConnection();

$notice_stmt = $conn->prepare("SELECT * FROM notices WHERE id = ?");
$notice_stmt->bind_param("i", $notice_id);
$notice_stmt->execute();
$notice = $notice_stmt->get_result()->fetch_assoc();
$notice_stmt->close();

if (!$notice) {
    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '公告不存在'], 404);
}

$template = null;
if ($template_id > 0) {
    $tpl_stmt = $conn->prepare("SELECT * FROM print_templates WHERE id = ?");
    $tpl_stmt->bind_param("i", $template_id);
    $tpl_stmt->execute();
    $template = $tpl_stmt->get_result()->fetch_assoc();
    $tpl_stmt->close();
}

if (!$template) {
    $tpl_result = $conn->query("SELECT * FROM print_templates WHERE is_default = 1 LIMIT 1");
    if ($tpl_result && $tpl_result->num_rows > 0) {
        $template = $tpl_result->fetch_assoc();
    } else {
        $tpl_result = $conn->query("SELECT * FROM print_templates LIMIT 1");
        if ($tpl_result && $tpl_result->num_rows > 0) {
            $template = $tpl_result->fetch_assoc();
        }
    }
}

if ($template && $template['style_json']) {
    $template['style'] = json_decode($template['style_json'], true);
} else {
    $template['style'] = new stdClass();
}
unset($template['style_json']);

closeConnection($conn);

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['REQUEST_URI']);
$detail_url = $protocol . $host . $path . '/notice_detail.php?id=' . $notice_id;

$qr_data = $detail_url;

$content_html = nl2br(htmlspecialchars($notice['content']));

$attachments = [];

$data = [
    'notice' => [
        'id' => $notice['id'],
        'title' => $notice['title'],
        'content' => $notice['content'],
        'content_html' => $content_html,
        'author' => $notice['author'],
        'publish_date' => date('Y-m-d H:i:s', strtotime($notice['publish_date'])),
        'publish_date_cn' => date('Y年n月j日', strtotime($notice['publish_date'])),
        'priority' => $notice['priority'],
        'priority_text' => ['high' => '高', 'medium' => '中', 'low' => '低'][$notice['priority']],
        'status' => $notice['status'],
        'status_text' => ['published' => '已发布', 'draft' => '草稿'][$notice['status']],
        'views' => $notice['views'],
    ],
    'template' => $template,
    'attachments' => $attachments,
    'detail_url' => $detail_url,
    'qr_data' => $qr_data,
    'meta' => [
        'generated_at' => date('Y-m-d H:i:s'),
        'page_count' => 1,
    ]
];

jsonResponse(['success' => true, 'data' => $data]);
