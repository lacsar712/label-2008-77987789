<?php
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => '仅支持POST请求']);
    exit;
}

require_once 'config.php';
ensureSubscriptionTables();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$conn = getConnection();

$subTypeLabels = ['category' => '分类', 'author' => '作者', 'keyword' => '关键词', 'priority' => '优先级'];
$pushStatusLabels = ['generated' => '已生成', 'sent' => '已发送', 'failed' => '失败'];

switch ($action) {
    case 'list_by_email':
        $email = sanitize($input['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
            break;
        }

        $stmt = $conn->prepare('SELECT id, email, sub_type, sub_value, is_paused, created_at, updated_at FROM subscriptions WHERE email = ? ORDER BY created_at DESC');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['sub_type_label'] = $subTypeLabels[$row['sub_type']] ?? $row['sub_type'];
            $row['is_paused'] = intval($row['is_paused']);
            $items[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => ['items' => $items]]);
        break;

    case 'detail':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $stmt = $conn->prepare('SELECT id, email, sub_type, sub_value, is_paused, created_at, updated_at FROM subscriptions WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $sub = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$sub) {
            echo json_encode(['success' => false, 'message' => '订阅不存在']);
            break;
        }
        $sub['sub_type_label'] = $subTypeLabels[$sub['sub_type']] ?? $sub['sub_type'];
        $sub['is_paused'] = intval($sub['is_paused']);

        echo json_encode(['success' => true, 'data' => $sub]);
        break;

    case 'create':
        $email = sanitize($input['email'] ?? '');
        $sub_type = $input['sub_type'] ?? '';
        $sub_value = sanitize($input['sub_value'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
            break;
        }
        if (!in_array($sub_type, ['category', 'author', 'keyword', 'priority'], true)) {
            echo json_encode(['success' => false, 'message' => '无效的订阅类型']);
            break;
        }
        if ($sub_value === '') {
            echo json_encode(['success' => false, 'message' => '订阅值不能为空']);
            break;
        }

        $check = $conn->prepare('SELECT id FROM subscriptions WHERE email = ? AND sub_type = ? AND sub_value = ?');
        $check->bind_param('sss', $email, $sub_type, $sub_value);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $check->close();
            echo json_encode(['success' => false, 'message' => '该订阅条件已存在']);
            break;
        }
        $check->close();

        $stmt = $conn->prepare('INSERT INTO subscriptions (email, sub_type, sub_value, is_paused) VALUES (?, ?, ?, 0)');
        $stmt->bind_param('sss', $email, $sub_type, $sub_value);
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'message' => '订阅创建成功', 'data' => ['id' => $newId]]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => '创建失败: ' . $conn->error]);
        }
        break;

    case 'update':
        $id = intval($input['id'] ?? 0);
        $sub_type = $input['sub_type'] ?? '';
        $sub_value = sanitize($input['sub_value'] ?? '');

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }
        if (!in_array($sub_type, ['category', 'author', 'keyword', 'priority'], true)) {
            echo json_encode(['success' => false, 'message' => '无效的订阅类型']);
            break;
        }
        if ($sub_value === '') {
            echo json_encode(['success' => false, 'message' => '订阅值不能为空']);
            break;
        }

        $stmt = $conn->prepare('UPDATE subscriptions SET sub_type = ?, sub_value = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('ssi', $sub_type, $sub_value, $id);
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => '订阅更新成功']);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => '更新失败: ' . $conn->error]);
        }
        break;

    case 'delete':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $stmt = $conn->prepare('DELETE FROM subscriptions WHERE id = ?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => '订阅删除成功']);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => '删除失败: ' . $conn->error]);
        }
        break;

    case 'toggle_pause':
        $id = intval($input['id'] ?? 0);
        $is_paused = isset($input['is_paused']) ? intval($input['is_paused']) : null;

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $stmt = $conn->prepare('SELECT is_paused FROM subscriptions WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo json_encode(['success' => false, 'message' => '订阅不存在']);
            break;
        }

        $new_status = $is_paused !== null ? $is_paused : ($row['is_paused'] ? 0 : 1);
        $stmt = $conn->prepare('UPDATE subscriptions SET is_paused = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('ii', $new_status, $id);
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => $new_status ? '已暂停' : '已恢复', 'data' => ['is_paused' => $new_status]]);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => '操作失败: ' . $conn->error]);
        }
        break;

    case 'batch_pause':
        $email = sanitize($input['email'] ?? '');
        $is_paused = intval($input['is_paused'] ?? 1);
        $ids = isset($input['ids']) ? array_map('intval', $input['ids']) : [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => '请输入有效的邮箱地址']);
            break;
        }

        if (count($ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $types = 's' . str_repeat('i', count($ids));
            $params = array_merge([$email], $ids);
            $stmt = $conn->prepare("UPDATE subscriptions SET is_paused = ?, updated_at = NOW() WHERE email = ? AND id IN ($placeholders)");
            array_unshift($params, $is_paused);
            array_unshift($params, 'i' . $types);
            call_user_func_array([$stmt, 'bind_param'], refValues($params));
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
        } else {
            $stmt = $conn->prepare('UPDATE subscriptions SET is_paused = ?, updated_at = NOW() WHERE email = ?');
            $stmt->bind_param('is', $is_paused, $email);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
        }

        echo json_encode(['success' => true, 'message' => '批量操作成功，共处理 ' . $affected . ' 条', 'data' => ['affected' => $affected]]);
        break;

    case 'push_list':
        $page = max(1, intval($input['page'] ?? 1));
        $per_page = max(1, intval($input['per_page'] ?? 10));
        $email = sanitize($input['email'] ?? '');
        $push_status = $input['push_status'] ?? null;

        $where = [];
        $params = [];
        $types = '';

        if ($email !== '') {
            $where[] = 's.email = ?';
            $params[] = $email;
            $types .= 's';
        }
        if ($push_status !== null && $push_status !== '') {
            $where[] = 'p.push_status = ?';
            $params[] = $push_status;
            $types .= 's';
        }

        $whereSql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS cnt FROM push_records p LEFT JOIN subscriptions s ON p.subscription_id = s.id $whereSql";
        $stmt = $conn->prepare($countSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['cnt'];
        $stmt->close();

        $total_pages = max(1, ceil($total / $per_page));
        $offset = ($page - 1) * $per_page;

        $dataSql = "SELECT p.id, p.subscription_id, p.notice_id, p.summary, p.push_status, p.pushed_at, p.created_at,
                           s.email, s.sub_type, s.sub_value,
                           n.title AS notice_title, n.author AS notice_author
                    FROM push_records p
                    LEFT JOIN subscriptions s ON p.subscription_id = s.id
                    LEFT JOIN notices n ON p.notice_id = n.id
                    $whereSql
                    ORDER BY p.created_at DESC
                    LIMIT ? OFFSET ?";
        $dataTypes = $types . 'ii';
        $dataParams = array_merge($params, [$per_page, $offset]);

        $stmt = $conn->prepare($dataSql);
        $stmt->bind_param($dataTypes, ...$dataParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $row['push_status_label'] = $pushStatusLabels[$row['push_status']] ?? $row['push_status'];
            $row['sub_type_label'] = $subTypeLabels[$row['sub_type']] ?? $row['sub_type'];
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

    case 'push_resend':
        $id = intval($input['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => '无效的ID']);
            break;
        }

        $stmt = $conn->prepare('SELECT id FROM push_records WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            echo json_encode(['success' => false, 'message' => '推送记录不存在']);
            break;
        }

        $stmt = $conn->prepare("UPDATE push_records SET push_status = 'generated', pushed_at = NULL, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            echo json_encode(['success' => true, 'message' => '已重新生成推送记录']);
        } else {
            $stmt->close();
            echo json_encode(['success' => false, 'message' => '操作失败: ' . $conn->error]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => '未知操作']);
        break;
}

function refValues($arr) {
    $refs = [];
    foreach ($arr as $key => $value) {
        $refs[$key] = &$arr[$key];
    }
    return $refs;
}

closeConnection($conn);
