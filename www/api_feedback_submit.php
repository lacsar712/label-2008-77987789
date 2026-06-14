<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

require_once 'config.php';
ensureFeedbackTables();

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$validTypes = ['bug', 'feature', 'complaint', 'suggestion', 'other'];

if (empty($data['type']) || !in_array($data['type'], $validTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'type字段无效，必须为bug/feature/complaint/suggestion/other之一']);
    exit;
}

if (empty($data['title'])) {
    echo json_encode(['success' => false, 'message' => 'title字段不能为空']);
    exit;
}

if (empty($data['description'])) {
    echo json_encode(['success' => false, 'message' => 'description字段不能为空']);
    exit;
}

$contact = isset($data['contact']) ? sanitize($data['contact']) : '';

$screenshots = [];
if (isset($data['screenshots'])) {
    $decoded = is_string($data['screenshots']) ? json_decode($data['screenshots'], true) : $data['screenshots'];
    if (is_array($decoded)) {
        $screenshots = array_slice($decoded, 0, 3);
    }
}
$screenshotsJson = json_encode($screenshots);

$type = sanitize($data['type']);
$title = sanitize($data['title']);
$description = sanitize($data['description']);

$conn = getConnection();

function generateTicketNo(): string
{
    $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $ticketNo = '';
    for ($i = 0; $i < 8; $i++) {
        $ticketNo .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $ticketNo;
}

do {
    $ticketNo = generateTicketNo();
    $stmt = $conn->prepare('SELECT COUNT(*) FROM feedbacks WHERE ticket_no = ?');
    $stmt->bind_param('s', $ticketNo);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
} while ($count > 0);

$conn->begin_transaction();

try {
    $stmt = $conn->prepare('INSERT INTO feedbacks (ticket_no, type, title, description, contact, screenshots, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
    $status = 'pending';
    $stmt->bind_param('sssssss', $ticketNo, $type, $title, $description, $contact, $screenshotsJson, $status);
    $stmt->execute();
    $feedbackId = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO feedback_timeline (feedback_id, from_status, to_status, note) VALUES (?, NULL, ?, ?)');
    $toStatus = 'pending';
    $note = '反馈已提交';
    $stmt->bind_param('iss', $feedbackId, $toStatus, $note);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    echo json_encode(['success' => true, 'ticket_no' => $ticketNo, 'message' => '反馈提交成功']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => '提交失败，请稍后重试']);
}

closeConnection($conn);
