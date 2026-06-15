<?php
require_once 'config.php';
ensureLotteryTables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = array_merge($_GET, $_POST);
}

$action = isset($data['action']) ? sanitize($data['action']) : '';

function getLotteryStatus($lottery) {
    $now = time();
    $start = strtotime($lottery['start_time']);
    $end = strtotime($lottery['end_time']);
    $draw = strtotime($lottery['draw_time']);

    if ($lottery['is_drawn']) {
        return 'finished';
    }
    if ($now < $start) {
        return 'upcoming';
    }
    if ($now >= $start && $now <= $end) {
        return 'ongoing';
    }
    if ($now > $end && $now < $draw) {
        return 'waiting_draw';
    }
    return 'draw_pending';
}

function formatLotteryRow($row, $conn) {
    $participantCount = 0;
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM lottery_participants WHERE lottery_id = ?");
    $stmt->bind_param('i', $row['id']);
    $stmt->execute();
    $participantCount = intval($stmt->get_result()->fetch_assoc()['cnt']);
    $stmt->close();

    $prizes = json_decode($row['prizes'], true);
    if (!is_array($prizes)) $prizes = [];

    $noticeTitle = null;
    if ($row['notice_id']) {
        $stmt = $conn->prepare("SELECT title FROM notices WHERE id = ?");
        $stmt->bind_param('i', $row['notice_id']);
        $stmt->execute();
        $nr = $stmt->get_result()->fetch_assoc();
        if ($nr) $noticeTitle = htmlspecialchars($nr['title']);
        $stmt->close();
    }

    return [
        'id' => intval($row['id']),
        'name' => htmlspecialchars($row['name']),
        'notice_id' => $row['notice_id'] ? intval($row['notice_id']) : null,
        'notice_title' => $noticeTitle,
        'prizes' => $prizes,
        'start_time' => $row['start_time'],
        'end_time' => $row['end_time'],
        'draw_time' => $row['draw_time'],
        'condition_text' => $row['condition_text'] ? htmlspecialchars($row['condition_text']) : null,
        'status' => $row['status'],
        'is_drawn' => boolval($row['is_drawn']),
        'participant_count' => $participantCount,
        'runtime_status' => getLotteryStatus($row),
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at']
    ];
}

if ($method === 'GET') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';

    if ($action === 'list') {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        $status = isset($_GET['status']) ? sanitize($_GET['status']) : '';
        $keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';

        $where = [];
        $params = [];
        $types = '';
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
            $types .= 's';
        }
        if (!empty($keyword)) {
            $where[] = "name LIKE ?";
            $params[] = "%$keyword%";
            $types .= 's';
        }
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $count_sql = "SELECT COUNT(*) as total FROM lotteries $whereSql";
        $stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sql = "SELECT * FROM lotteries $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $types .= 'ii';
            $params[] = $per_page;
            $params[] = $offset;
            $stmt->bind_param($types, ...$params);
        } else {
            $stmt->bind_param('ii', $per_page, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $lotteries = [];
        while ($row = $result->fetch_assoc()) {
            $lotteries[] = formatLotteryRow($row, $conn);
        }
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'list' => $lotteries,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => intval($total),
                    'total_pages' => ceil($total / $per_page)
                ]
            ]
        ]);

    } elseif ($action === 'detail') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
        }

        closeConnection($conn);
        jsonResponse(['success' => true, 'data' => formatLotteryRow($row, $conn)]);

    } elseif ($action === 'group_list') {
        $now = date('Y-m-d H:i:s');
        $sql = "SELECT * FROM lotteries WHERE status IN ('active', 'finished') ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $groups = [
            'ongoing' => [],
            'upcoming' => [],
            'finished' => []
        ];

        while ($row = $result->fetch_assoc()) {
            $item = formatLotteryRow($row, $conn);
            $rs = $item['runtime_status'];
            if ($rs === 'finished') {
                $groups['finished'][] = $item;
            } elseif ($rs === 'upcoming') {
                $groups['upcoming'][] = $item;
            } else {
                $groups['ongoing'][] = $item;
            }
        }

        closeConnection($conn);
        jsonResponse(['success' => true, 'data' => $groups]);

    } elseif ($action === 'eligibility') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $lottery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$lottery) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
        }

        $visitorId = getUserIdentifier();
        $eligible = true;
        $reason = '';

        if ($lottery['status'] !== 'active') {
            $eligible = false;
            $reason = '活动未启用';
        } elseif ($lottery['is_drawn']) {
            $eligible = false;
            $reason = '活动已开奖';
        } else {
            $now = time();
            $start = strtotime($lottery['start_time']);
            $end = strtotime($lottery['end_time']);
            if ($now < $start) {
                $eligible = false;
                $reason = '活动尚未开始';
            } elseif ($now > $end) {
                $eligible = false;
                $reason = '活动已结束参与';
            }
        }

        $hasParticipated = false;
        if ($eligible) {
            $stmt = $conn->prepare("SELECT id FROM lottery_participants WHERE lottery_id = ? AND visitor_id = ?");
            $stmt->bind_param('is', $id, $visitorId);
            $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $hasParticipated = true;
                $eligible = false;
                $reason = '您已参与过该活动';
            }
            $stmt->close();
        }

        closeConnection($conn);
        jsonResponse([
            'success' => true,
            'data' => [
                'eligible' => $eligible,
                'reason' => $reason,
                'has_participated' => $hasParticipated
            ]
        ]);

    } elseif ($action === 'winners') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM lottery_winners WHERE lottery_id = ? ORDER BY drawn_at ASC, id ASC");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $winners = [];
        while ($row = $result->fetch_assoc()) {
            $winners[] = [
                'id' => intval($row['id']),
                'lottery_id' => intval($row['lottery_id']),
                'prize_name' => htmlspecialchars($row['prize_name']),
                'visitor_id' => substr($row['visitor_id'], 0, 8) . '...',
                'drawn_at' => $row['drawn_at']
            ];
        }
        $stmt->close();
        closeConnection($conn);

        jsonResponse(['success' => true, 'data' => $winners]);

    } elseif ($action === 'toggle_status') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT status, is_drawn FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
        }
        if ($row['is_drawn']) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '已开奖活动无法修改状态'], 400);
        }

        $newStatus = $row['status'] === 'active' ? 'paused' : 'active';
        $stmt = $conn->prepare("UPDATE lotteries SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $id);
        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => '状态更新成功', 'data' => ['status' => $newStatus]]);
        } else {
            jsonResponse(['success' => false, 'message' => '状态更新失败'], 500);
        }
    }

} elseif ($method === 'POST') {
    $action = isset($data['action']) ? sanitize($data['action']) : 'create';

    if ($action === 'create' || $action === 'update') {
        $id = $action === 'update' ? (isset($data['id']) ? intval($data['id']) : 0) : 0;
        $name = isset($data['name']) ? sanitize($data['name']) : '';
        $notice_id = isset($data['notice_id']) && !empty($data['notice_id']) ? intval($data['notice_id']) : null;
        $prizes = isset($data['prizes']) ? $data['prizes'] : [];
        $start_time = isset($data['start_time']) ? sanitize($data['start_time']) : '';
        $end_time = isset($data['end_time']) ? sanitize($data['end_time']) : '';
        $draw_time = isset($data['draw_time']) ? sanitize($data['draw_time']) : '';
        $condition_text = isset($data['condition_text']) ? sanitize($data['condition_text']) : null;
        $status = isset($data['status']) ? sanitize($data['status']) : 'draft';

        if (empty($name)) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '请填写活动名称'], 400);
        }
        if (empty($start_time) || empty($end_time) || empty($draw_time)) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '请填写完整的时间信息'], 400);
        }
        if (strtotime($end_time) <= strtotime($start_time)) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '结束时间必须晚于开始时间'], 400);
        }
        if (strtotime($draw_time) < strtotime($end_time)) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '开奖时间不能早于结束时间'], 400);
        }
        if (!is_array($prizes) || count($prizes) === 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '请至少添加一个奖品'], 400);
        }
        foreach ($prizes as $p) {
            if (empty($p['name']) || !isset($p['count']) || intval($p['count']) <= 0) {
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '奖品配置不完整'], 400);
            }
        }
        if ($action === 'update' && $id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }
        if (!in_array($status, ['draft', 'active', 'paused'])) {
            $status = 'draft';
        }

        $prizesJson = json_encode($prizes, JSON_UNESCAPED_UNICODE);

        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO lotteries (name, notice_id, prizes, start_time, end_time, draw_time, condition_text, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sissssss', $name, $notice_id, $prizesJson, $start_time, $end_time, $draw_time, $condition_text, $status);
            $result = $stmt->execute();
            $lottery_id = $conn->insert_id;
            $stmt->close();
        } else {
            $stmt = $conn->prepare("SELECT id, is_drawn FROM lotteries WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $exist = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$exist) {
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
            }
            if ($exist['is_drawn']) {
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '已开奖活动无法修改'], 400);
            }

            $lottery_id = $id;
            $stmt = $conn->prepare("UPDATE lotteries SET name = ?, notice_id = ?, prizes = ?, start_time = ?, end_time = ?, draw_time = ?, condition_text = ?, status = ? WHERE id = ?");
            $stmt->bind_param('sissssssi', $name, $notice_id, $prizesJson, $start_time, $end_time, $draw_time, $condition_text, $status, $id);
            $result = $stmt->execute();
            $stmt->close();
        }

        closeConnection($conn);
        if ($result) {
            jsonResponse([
                'success' => true,
                'message' => $action === 'create' ? '活动创建成功' : '活动更新成功',
                'data' => ['id' => $lottery_id]
            ]);
        } else {
            jsonResponse(['success' => false, 'message' => '操作失败'], 500);
        }

    } elseif ($action === 'delete') {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT id FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
        }
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => '活动删除成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '删除失败'], 500);
        }

    } elseif ($action === 'participate') {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM lotteries WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $lottery = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$lottery) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '活动不存在'], 404);
        }

        $visitorId = getUserIdentifier();

        if ($lottery['status'] !== 'active') {
            closeConnection($conn);
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '活动未启用']);
        }
        if ($lottery['is_drawn']) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '活动已开奖']);
        }

        $now = time();
        $start = strtotime($lottery['start_time']);
        $end = strtotime($lottery['end_time']);
        if ($now < $start) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '活动尚未开始']);
        }
        if ($now > $end) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '活动已结束参与']);
        }

        $checkStmt = $conn->prepare("SELECT id FROM lottery_participants WHERE lottery_id = ? AND visitor_id = ?");
        $checkStmt->bind_param('is', $id, $visitorId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->fetch_assoc()) {
            $checkStmt->close();
            closeConnection($conn);
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '您已参与过该活动', 'already_participated' => true]);
        }
        $checkStmt->close();

        $stmt = $conn->prepare("INSERT INTO lottery_participants (lottery_id, visitor_id) VALUES (?, ?)");
        $stmt->bind_param('is', $id, $visitorId);
        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'eligible' => true, 'message' => '参与成功']);
        } else {
            jsonResponse(['success' => false, 'eligible' => false, 'message' => '参与失败，请重试']);
        }

    } elseif ($action === 'draw') {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的活动ID'], 400);
        }

        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT * FROM lotteries WHERE id = ? FOR UPDATE");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $lottery = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$lottery) {
                throw new Exception('活动不存在', 404);
            }
            if ($lottery['is_drawn']) {
                throw new Exception('该活动已开奖', 400);
            }

            $now = time();
            $drawTime = strtotime($lottery['draw_time']);
            if ($now < $drawTime) {
                throw new Exception('尚未到开奖时间', 400);
            }

            $prizes = json_decode($lottery['prizes'], true);
            if (!is_array($prizes)) {
                throw new Exception('奖品配置错误', 500);
            }

            $stmt = $conn->prepare("SELECT visitor_id FROM lottery_participants WHERE lottery_id = ? ORDER BY RAND()");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $participantsResult = $stmt->get_result();
            $participants = [];
            while ($row = $participantsResult->fetch_assoc()) {
                $participants[] = $row['visitor_id'];
            }
            $stmt->close();

            $winners = [];
            $usedParticipants = [];
            $totalPrizeCount = 0;
            foreach ($prizes as $prize) {
                $totalPrizeCount += intval($prize['count']);
            }

            foreach ($prizes as $prize) {
                $prizeName = $prize['name'];
                $count = intval($prize['count']);
                for ($i = 0; $i < $count; $i++) {
                    if (count($participants) === 0) break;
                    $idx = array_rand($participants);
                    $winner = $participants[$idx];
                    if (in_array($winner, $usedParticipants)) {
                        $i--;
                        array_splice($participants, $idx, 1);
                        continue;
                    }
                    $usedParticipants[] = $winner;
                    $winners[] = [
                        'prize_name' => $prizeName,
                        'visitor_id' => $winner
                    ];
                    $stmt = $conn->prepare("INSERT INTO lottery_winners (lottery_id, prize_name, visitor_id) VALUES (?, ?, ?)");
                    $stmt->bind_param('iss', $id, $prizeName, $winner);
                    $stmt->execute();
                    $stmt->close();
                    array_splice($participants, $idx, 1);
                }
            }

            $stmt = $conn->prepare("UPDATE lotteries SET is_drawn = 1, status = 'finished' WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();
            closeConnection($conn);

            jsonResponse([
                'success' => true,
                'message' => '开奖成功',
                'data' => [
                    'winner_count' => count($winners),
                    'winners' => array_map(function($w) {
                        return [
                            'prize_name' => $w['prize_name'],
                            'visitor_id' => substr($w['visitor_id'], 0, 8) . '...'
                        ];
                    }, $winners)
                ]
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            closeConnection($conn);
            $code = $e->getCode() >= 400 ? $e->getCode() : 500;
            jsonResponse(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
