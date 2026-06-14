<?php
header('Content-Type: application/json; charset=UTF-8');

require_once 'config.php';
ensureFeedbackTables();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'message' => '请求方法不允许']);
    exit;
}

$ticket_no = $_GET['ticket_no'] ?? '';

if ($ticket_no === '') {
    echo json_encode(['success' => false, 'message' => '工单号不能为空']);
    exit;
}

if (!ctype_alnum($ticket_no) || strlen($ticket_no) !== 8) {
    echo json_encode(['success' => false, 'message' => '工单号格式不正确']);
    exit;
}

$conn = getConnection();

$stmt = $conn->prepare("SELECT id, ticket_no, type, title, description, contact, screenshots, status, created_at, updated_at FROM feedbacks WHERE ticket_no = ?");
$stmt->bind_param("s", $ticket_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    closeConnection($conn);
    echo json_encode(['success' => false, 'message' => '工单不存在']);
    exit;
}

$feedback = $result->fetch_assoc();
$feedback_id = $feedback['id'];
$stmt->close();

$stmt = $conn->prepare("SELECT from_status, to_status, note, created_at FROM feedback_timeline WHERE feedback_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$timeline_result = $stmt->get_result();

$timeline = [];
while ($row = $timeline_result->fetch_assoc()) {
    $timeline[] = [
        'from_status' => $row['from_status'],
        'to_status' => $row['to_status'],
        'note' => $row['note'],
        'created_at' => $row['created_at']
    ];
}
$stmt->close();
closeConnection($conn);

$screenshots = $feedback['screenshots'];
if (is_string($screenshots)) {
    $decoded = json_decode($screenshots, true);
    $screenshots = is_array($decoded) ? $decoded : [];
}

echo json_encode([
    'success' => true,
    'data' => [
        'ticket_no' => $feedback['ticket_no'],
        'type' => $feedback['type'],
        'title' => $feedback['title'],
        'description' => $feedback['description'],
        'contact' => $feedback['contact'],
        'screenshots' => $screenshots,
        'status' => $feedback['status'],
        'created_at' => $feedback['created_at'],
        'updated_at' => $feedback['updated_at'],
        'timeline' => $timeline
    ]
]);
