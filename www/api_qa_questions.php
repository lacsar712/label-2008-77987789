<?php
require_once 'config.php';
ensureQATables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();

if ($method === 'GET') {
    $notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    $offset = ($page - 1) * $per_page;

    if ($notice_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
    }

    $count_sql = "SELECT COUNT(*) as total FROM questions WHERE notice_id = ?";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param("i", $notice_id);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $sql = "SELECT q.*, 
                   (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count
            FROM questions q 
            WHERE q.notice_id = ? 
            ORDER BY q.created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $notice_id, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = [
            'id' => intval($row['id']),
            'notice_id' => intval($row['notice_id']),
            'asker' => htmlspecialchars($row['asker']),
            'content' => htmlspecialchars($row['content']),
            'status' => $row['status'],
            'best_answer_id' => $row['best_answer_id'] ? intval($row['best_answer_id']) : null,
            'views' => intval($row['views']),
            'answer_count' => intval($row['answer_count']),
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }
    $stmt->close();
    closeConnection($conn);

    jsonResponse([
        'success' => true,
        'data' => [
            'list' => $questions,
            'pagination' => [
                'page' => $page,
                'per_page' => $per_page,
                'total' => intval($total),
                'total_pages' => ceil($total / $per_page)
            ]
        ]
    ]);
} elseif ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $notice_id = isset($data['notice_id']) ? intval($data['notice_id']) : 0;
    $asker = isset($data['asker']) ? sanitize($data['asker']) : '';
    $content = isset($data['content']) ? sanitize($data['content']) : '';

    if ($notice_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
    }
    if (empty($asker)) {
        jsonResponse(['success' => false, 'message' => '请填写提问者名称'], 400);
    }
    if (empty($content)) {
        jsonResponse(['success' => false, 'message' => '请填写提问内容'], 400);
    }

    $check_stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
    $check_stmt->bind_param("i", $notice_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        $check_stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '公告不存在'], 404);
    }
    $check_stmt->close();

    $stmt = $conn->prepare("INSERT INTO questions (notice_id, asker, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $notice_id, $asker, $content);

    if ($stmt->execute()) {
        $question_id = $conn->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'message' => '提问成功',
            'data' => [
                'id' => intval($row['id']),
                'notice_id' => intval($row['notice_id']),
                'asker' => htmlspecialchars($row['asker']),
                'content' => htmlspecialchars($row['content']),
                'status' => $row['status'],
                'best_answer_id' => $row['best_answer_id'] ? intval($row['best_answer_id']) : null,
                'views' => intval($row['views']),
                'answer_count' => 0,
                'created_at' => $row['created_at']
            ]
        ]);
    } else {
        $stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '提问失败：' . $conn->error], 500);
    }
} else {
    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
}
