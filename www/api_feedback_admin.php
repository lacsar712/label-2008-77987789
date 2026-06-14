<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

require_once 'config.php';
ensureFeedbackTables();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$conn = getConnection();

switch ($action) {
    case 'list':
        $page = max(1, intval($input['page'] ?? 1));
        $per_page = max(1, intval($input['per_page'] ?? 10));
        $status = $input['status'] ?? null;
        $type = $input['type'] ?? null;
        $keyword = $input['keyword'] ?? null;

        $where = [];
        $params = [];
        $types = '';

        if ($status !== null) {
            $where[] = 'f.status = ?';
            $params[] = sanitize($status);
            $types .= 's';
        }
        if ($type !== null) {
            $where[] = 'f.type = ?';
            $params[] = sanitize($type);
            $types .= 's';
        }
        if ($keyword !== null && $keyword !== '') {
            $where[] = '(f.title LIKE ? OR f.description LIKE ?)';
            $kw = '%' . sanitize($keyword) . '%';
            $params[] = $kw;
            $params[] = $kw;
            $types .= 'ss';
        }

        $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS cnt FROM feedbacks f $whereSql";
        $stmt = $conn->prepare($countSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        $total_pages = max(1, ceil($total / $per_page));
        $offset = ($page - 1) * $per_page;

        $dataSql = "SELECT f.id, f.ticket_no, f.type, f.title, f.description, f.contact, f.screenshots, f.status, f.internal_note, f.created_at, f.updated_at FROM feedbacks f $whereSql ORDER BY f.created_at DESC LIMIT ? OFFSET ?";
        $dataTypes = $types . 'ii';
        $dataParams = array_merge($params, [$per_page, $offset]);

        $stmt = $conn->prepare($dataSql);
        $stmt->bind_param($dataTypes, ...$dataParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['screenshots'] = json_decode($row['screenshots'], true);
            $items[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'data' => [
                'total' => intval($total),
                'page' => $page,
                'per_page' => $per_page,
                'total_pages' => intval($total_pages),
                'items' => $items
            ]
        ]);
        break;

    case 'detail':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $stmt = $conn->prepare('SELECT id, ticket_no, type, title, description, contact, screenshots, status, internal_note, created_at, updated_at FROM feedbacks WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $feedback = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$feedback) {
            echo json_encode(['success' => false, 'message' => '反馈不存在']);
            break;
        }

        $feedback['screenshots'] = json_decode($feedback['screenshots'], true);

        $stmt = $conn->prepare('SELECT id, feedback_id, from_status, to_status, note, created_at FROM feedback_timeline WHERE feedback_id = ? ORDER BY created_at ASC');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $timeline = [];
        while ($row = $result->fetch_assoc()) {
            $timeline[] = $row;
        }
        $stmt->close();

        $feedback['timeline'] = $timeline;

        echo json_encode(['success' => true, 'data' => $feedback]);
        break;

    case 'update_status':
        $id = intval($input['id'] ?? 0);
        $status = $input['status'] ?? '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $allowed = ['pending', 'processing', 'resolved', 'closed'];
        if (!in_array($status, $allowed, true)) {
            echo json_encode(['success' => false, 'message' => '无效的状态']);
            break;
        }

        $stmt = $conn->prepare('SELECT status FROM feedbacks WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '反馈不存在']);
            break;
        }

        if ($row['status'] === $status) {
            echo json_encode(['success' => true, 'message' => '状态未变化']);
            break;
        }

        $old_status = $row['status'];

        $stmt = $conn->prepare('UPDATE feedbacks SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO feedback_timeline (feedback_id, from_status, to_status, note, created_at) VALUES (?, ?, ?, \'\', NOW())');
        $stmt->bind_param('iss', $id, $old_status, $status);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => '状态更新成功']);
        break;

    case 'add_note':
        $id = intval($input['id'] ?? 0);
        $note = $input['note'] ?? '';

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        if ($note === '') {
            echo json_encode(['success' => false, 'message' => '备注内容不能为空']);
            break;
        }

        $stmt = $conn->prepare('SELECT status, internal_note FROM feedbacks WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '反馈不存在']);
            break;
        }

        $current_status = $row['status'];
        $existing_note = $row['internal_note'];
        $new_note = $existing_note === null ? $note : $existing_note . "\n" . $note;

        $stmt = $conn->prepare('UPDATE feedbacks SET internal_note = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('si', $new_note, $id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('INSERT INTO feedback_timeline (feedback_id, from_status, to_status, note, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('isss', $id, $current_status, $current_status, $note);
        $stmt->execute();
        $stmt->close();

        echo json_encode(['success' => true, 'message' => '备注添加成功']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}

closeConnection($conn);
