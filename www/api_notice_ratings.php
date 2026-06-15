<?php
require_once 'config.php';
ensureRatingTables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();
$visitor_id = getUserIdentifier();

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? sanitize($input['action']) : '';

    if ($action === 'submit') {
        $notice_id = isset($input['notice_id']) ? intval($input['notice_id']) : 0;
        $score = isset($input['score']) ? intval($input['score']) : 0;
        $comment = isset($input['comment']) ? sanitize($input['comment']) : '';

        if ($notice_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
        }
        if ($score < 1 || $score > 5) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '评分必须在1-5之间'], 400);
        }

        $stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
        $stmt->bind_param('i', $notice_id);
        $stmt->execute();
        $notice_exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$notice_exists) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '公告不存在'], 404);
        }

        $stmt = $conn->prepare("SELECT id FROM notice_ratings WHERE notice_id = ? AND visitor_id = ?");
        $stmt->bind_param('is', $notice_id, $visitor_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE notice_ratings SET score = ?, comment = ? WHERE id = ?");
            $stmt->bind_param('isi', $score, $comment, $existing['id']);
            $result = $stmt->execute();
            $stmt->close();
        } else {
            $stmt = $conn->prepare("INSERT INTO notice_ratings (notice_id, visitor_id, score, comment) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('isis', $notice_id, $visitor_id, $score, $comment);
            $result = $stmt->execute();
            $stmt->close();
        }

        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => $existing ? '评分已更新' : '评分已提交']);
        } else {
            jsonResponse(['success' => false, 'message' => '操作失败'], 500);
        }
    }

    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '不支持的操作'], 400);
}

if ($method === 'GET') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : 'my_rating';

    if ($action === 'my_rating') {
        $notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
        if ($notice_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
        }

        $stmt = $conn->prepare("SELECT score, comment FROM notice_ratings WHERE notice_id = ? AND visitor_id = ?");
        $stmt->bind_param('is', $notice_id, $visitor_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => $result ? [
                'score' => intval($result['score']),
                'comment' => $result['comment'] ? htmlspecialchars($result['comment']) : ''
            ] : null
        ]);
    }

    if ($action === 'aggregate') {
        $notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
        if ($notice_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
        }

        $stmt = $conn->prepare("SELECT COUNT(*) as total_count, AVG(score) as avg_score FROM notice_ratings WHERE notice_id = ?");
        $stmt->bind_param('i', $notice_id);
        $stmt->execute();
        $basic = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT score, COUNT(*) as count FROM notice_ratings WHERE notice_id = ? GROUP BY score ORDER BY score DESC");
        $stmt->bind_param('i', $notice_id);
        $stmt->execute();
        $dist_result = $stmt->get_result();
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        while ($row = $dist_result->fetch_assoc()) {
            $distribution[intval($row['score'])] = intval($row['count']);
        }
        $stmt->close();

        $stmt = $conn->prepare("SELECT comment, created_at FROM notice_ratings WHERE notice_id = ? AND comment IS NOT NULL AND comment != '' ORDER BY created_at DESC LIMIT 1");
        $stmt->bind_param('i', $notice_id);
        $stmt->execute();
        $latest = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'total_count' => intval($basic['total_count']),
                'avg_score' => $basic['avg_score'] ? round(floatval($basic['avg_score']), 2) : 0,
                'distribution' => $distribution,
                'latest_comment' => $latest ? [
                    'comment' => htmlspecialchars($latest['comment']),
                    'created_at' => $latest['created_at']
                ] : null
            ]
        ]);
    }

    if ($action === 'list') {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $score = isset($_GET['score']) ? intval($_GET['score']) : 0;
        $notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
        $keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';
        $start_date = isset($_GET['start_date']) ? sanitize($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize($_GET['end_date']) : '';
        $offset = ($page - 1) * $per_page;

        $where = [];
        $params = [];
        $types = '';

        if ($score > 0) {
            $where[] = 'r.score = ?';
            $params[] = $score;
            $types .= 'i';
        }
        if ($notice_id > 0) {
            $where[] = 'r.notice_id = ?';
            $params[] = $notice_id;
            $types .= 'i';
        }
        if ($keyword !== '') {
            $where[] = '(r.comment LIKE ? OR n.title LIKE ?)';
            $like = '%' . $keyword . '%';
            $params[] = $like;
            $params[] = $like;
            $types .= 'ss';
        }
        if ($start_date !== '') {
            $where[] = 'DATE(r.created_at) >= ?';
            $params[] = $start_date;
            $types .= 's';
        }
        if ($end_date !== '') {
            $where[] = 'DATE(r.created_at) <= ?';
            $params[] = $end_date;
            $types .= 's';
        }

        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_sql = "SELECT COUNT(*) as total FROM notice_ratings r LEFT JOIN notices n ON r.notice_id = n.id $where_sql";
        $stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = intval($stmt->get_result()->fetch_assoc()['total']);
        $stmt->close();

        $list_sql = "SELECT r.id, r.notice_id, r.score, r.comment, r.created_at, n.title as notice_title 
                     FROM notice_ratings r 
                     LEFT JOIN notices n ON r.notice_id = n.id 
                     $where_sql 
                     ORDER BY r.created_at DESC 
                     LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $list_params = $params;
        $list_params[] = $per_page;
        $list_params[] = $offset;
        $list_types = $types . 'ii';
        if (!empty($list_params)) {
            $stmt->bind_param($list_types, ...$list_params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $list[] = [
                'id' => intval($row['id']),
                'notice_id' => intval($row['notice_id']),
                'notice_title' => htmlspecialchars($row['notice_title']),
                'score' => intval($row['score']),
                'comment' => $row['comment'] ? htmlspecialchars($row['comment']) : '',
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'total_pages' => ceil($total / $per_page)
                ]
            ]
        ]);
    }

    if ($action === 'summary') {
        $sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'avg_desc';
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        $sort_map = [
            'avg_desc' => 'avg_score DESC',
            'avg_asc' => 'avg_score ASC',
            'count_desc' => 'total_count DESC',
            'count_asc' => 'total_count ASC',
            'date_desc' => 'n.publish_date DESC'
        ];
        $order_by = isset($sort_map[$sort]) ? $sort_map[$sort] : $sort_map['avg_desc'];

        $count_sql = "SELECT COUNT(DISTINCT r.notice_id) as total FROM notice_ratings r";
        $total = intval($conn->query($count_sql)->fetch_assoc()['total']);

        $list_sql = "SELECT 
            r.notice_id,
            n.title as notice_title,
            n.publish_date,
            COUNT(*) as total_count,
            AVG(r.score) as avg_score,
            SUM(CASE WHEN r.score = 5 THEN 1 ELSE 0 END) as star_5,
            SUM(CASE WHEN r.score = 4 THEN 1 ELSE 0 END) as star_4,
            SUM(CASE WHEN r.score = 3 THEN 1 ELSE 0 END) as star_3,
            SUM(CASE WHEN r.score = 2 THEN 1 ELSE 0 END) as star_2,
            SUM(CASE WHEN r.score = 1 THEN 1 ELSE 0 END) as star_1
        FROM notice_ratings r
        LEFT JOIN notices n ON r.notice_id = n.id
        GROUP BY r.notice_id
        ORDER BY $order_by
        LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($list_sql);
        $stmt->bind_param('ii', $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $list = [];
        while ($row = $result->fetch_assoc()) {
            $nid = intval($row['notice_id']);
            $latest_stmt = $conn->prepare("SELECT comment, created_at FROM notice_ratings WHERE notice_id = ? AND comment IS NOT NULL AND comment != '' ORDER BY created_at DESC LIMIT 1");
            $latest_stmt->bind_param('i', $nid);
            $latest_stmt->execute();
            $latest = $latest_stmt->get_result()->fetch_assoc();
            $latest_stmt->close();

            $list[] = [
                'notice_id' => $nid,
                'notice_title' => htmlspecialchars($row['notice_title']),
                'publish_date' => $row['publish_date'],
                'total_count' => intval($row['total_count']),
                'avg_score' => round(floatval($row['avg_score']), 2),
                'distribution' => [
                    5 => intval($row['star_5']),
                    4 => intval($row['star_4']),
                    3 => intval($row['star_3']),
                    2 => intval($row['star_2']),
                    1 => intval($row['star_1'])
                ],
                'latest_comment' => $latest ? [
                    'comment' => htmlspecialchars($latest['comment']),
                    'created_at' => $latest['created_at']
                ] : null
            ];
        }
        $stmt->close();

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'list' => $list,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => $total,
                    'total_pages' => ceil($total / $per_page)
                ]
            ]
        ]);
    }

    if ($action === 'notice_options') {
        $result = $conn->query("SELECT id, title FROM notices ORDER BY publish_date DESC LIMIT 100");
        $notices = [];
        while ($row = $result->fetch_assoc()) {
            $notices[] = [
                'id' => intval($row['id']),
                'title' => htmlspecialchars($row['title'])
            ];
        }
        closeConnection($conn);
        jsonResponse(['success' => true, 'data' => $notices]);
    }

    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '不支持的操作'], 400);
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
