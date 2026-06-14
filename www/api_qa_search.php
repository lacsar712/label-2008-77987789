<?php
require_once 'config.php';
ensureQATables();

$conn = getConnection();

$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
$offset = ($page - 1) * $per_page;

$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'time';

$where_clauses = [];
$params = [];
$types = '';

if (!empty($status) && in_array($status, ['open', 'resolved'])) {
    $where_clauses[] = "q.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($keyword)) {
    $where_clauses[] = "(q.content LIKE ? OR a.content LIKE ? OR n.title LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $types .= 'sss';
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(" AND ", $where_clauses) : "";

$count_sql = "SELECT COUNT(DISTINCT q.id) as total 
              FROM questions q 
              LEFT JOIN answers a ON a.question_id = q.id 
              LEFT JOIN notices n ON n.id = q.notice_id 
              $where_sql";

if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $total = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $total_result = $conn->query($count_sql);
    $total = $total_result->fetch_assoc()['total'];
}

$order_sql = "";
if ($sort === 'hot') {
    $order_sql = "ORDER BY answer_count DESC, q.created_at DESC";
} else {
    $order_sql = "ORDER BY q.created_at DESC";
}

$sql = "SELECT q.*, n.title as notice_title, n.author as notice_author,
               (SELECT COUNT(*) FROM answers a WHERE a.question_id = q.id) as answer_count,
               (SELECT COALESCE(SUM(a2.likes), 0) FROM answers a2 WHERE a2.question_id = q.id) as total_likes
        FROM questions q 
        LEFT JOIN notices n ON n.id = q.notice_id 
        $where_sql
        GROUP BY q.id
        $order_sql
        LIMIT ? OFFSET ?";

$params[] = $per_page;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$questions = [];
while ($row = $result->fetch_assoc()) {
    $questions[] = [
        'id' => intval($row['id']),
        'notice_id' => intval($row['notice_id']),
        'notice_title' => htmlspecialchars($row['notice_title'] ?? ''),
        'asker' => htmlspecialchars($row['asker']),
        'content' => htmlspecialchars($row['content']),
        'status' => $row['status'],
        'best_answer_id' => $row['best_answer_id'] ? intval($row['best_answer_id']) : null,
        'views' => intval($row['views']),
        'answer_count' => intval($row['answer_count']),
        'total_likes' => intval($row['total_likes']),
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
