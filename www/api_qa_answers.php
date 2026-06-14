<?php
require_once 'config.php';
ensureQATables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();
$userIdentifier = getUserIdentifier();

if ($method === 'GET') {
    $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;

    if ($question_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的问题ID'], 400);
    }

    $check_stmt = $conn->prepare("SELECT id FROM questions WHERE id = ?");
    $check_stmt->bind_param("i", $question_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        $check_stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问题不存在'], 404);
    }
    $check_stmt->close();

    $sql = "SELECT a.*, 
                   CASE WHEN al.id IS NOT NULL THEN 1 ELSE 0 END as liked
            FROM answers a
            LEFT JOIN answer_likes al ON al.answer_id = a.id AND al.user_identifier = ?
            WHERE a.question_id = ? 
            ORDER BY a.is_best DESC, a.likes DESC, a.created_at ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $userIdentifier, $question_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $answers = [];
    while ($row = $result->fetch_assoc()) {
        $answers[] = [
            'id' => intval($row['id']),
            'question_id' => intval($row['question_id']),
            'answerer' => htmlspecialchars($row['answerer']),
            'content' => htmlspecialchars($row['content']),
            'likes' => intval($row['likes']),
            'is_best' => boolval($row['is_best']),
            'liked' => boolval($row['liked']),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    $stmt->close();
    closeConnection($conn);

    jsonResponse(['success' => true, 'data' => $answers]);
} elseif ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $question_id = isset($data['question_id']) ? intval($data['question_id']) : 0;
    $answerer = isset($data['answerer']) ? sanitize($data['answerer']) : '';
    $content = isset($data['content']) ? sanitize($data['content']) : '';

    if ($question_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的问题ID'], 400);
    }
    if (empty($answerer)) {
        jsonResponse(['success' => false, 'message' => '请填写回答者名称'], 400);
    }
    if (empty($content)) {
        jsonResponse(['success' => false, 'message' => '请填写回答内容'], 400);
    }

    $check_stmt = $conn->prepare("SELECT id FROM questions WHERE id = ?");
    $check_stmt->bind_param("i", $question_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        $check_stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问题不存在'], 404);
    }
    $check_stmt->close();

    $stmt = $conn->prepare("INSERT INTO answers (question_id, answerer, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $question_id, $answerer, $content);

    if ($stmt->execute()) {
        $answer_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM answers WHERE id = ?");
        $stmt->bind_param("i", $answer_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'message' => '回答成功',
            'data' => [
                'id' => intval($row['id']),
                'question_id' => intval($row['question_id']),
                'answerer' => htmlspecialchars($row['answerer']),
                'content' => htmlspecialchars($row['content']),
                'likes' => intval($row['likes']),
                'is_best' => boolval($row['is_best']),
                'liked' => false,
                'created_at' => $row['created_at']
            ]
        ]);
    } else {
        $stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '回答失败：' . $conn->error], 500);
    }
} else {
    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
}
