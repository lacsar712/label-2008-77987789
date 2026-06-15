<?php
require_once 'config.php';
ensureSurveyTables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = array_merge($_GET, $_POST);
}

$action = isset($data['action']) ? sanitize($data['action']) : '';

if ($method === 'GET') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : 'list';

    if ($action === 'list') {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $per_page;
        $keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';

        $where = '';
        $params = [];
        $types = '';
        if (!empty($keyword)) {
            $where = "WHERE title LIKE ?";
            $params[] = "%$keyword%";
            $types .= 's';
        }

        $count_sql = "SELECT COUNT(*) as total FROM surveys $where";
        $stmt = $conn->prepare($count_sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sql = "SELECT s.*, 
                       (SELECT COUNT(*) FROM survey_questions q WHERE q.survey_id = s.id) as question_count,
                       (SELECT COUNT(DISTINCT visitor_id) FROM survey_answers a WHERE a.survey_id = s.id) as response_count
                FROM surveys s
                $where
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?";
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

        $surveys = [];
        while ($row = $result->fetch_assoc()) {
            $surveys[] = [
                'id' => intval($row['id']),
                'title' => htmlspecialchars($row['title']),
                'description' => $row['description'] ? htmlspecialchars($row['description']) : null,
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time'],
                'is_enabled' => boolval($row['is_enabled']),
                'question_count' => intval($row['question_count']),
                'response_count' => intval($row['response_count']),
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'list' => $surveys,
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
            jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $survey = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$survey) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷不存在'], 404);
        }

        $stmt = $conn->prepare("SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $questions_result = $stmt->get_result();
        $questions = [];
        $question_ids = [];
        while ($row = $questions_result->fetch_assoc()) {
            $question_ids[] = $row['id'];
            $questions[] = [
                'id' => intval($row['id']),
                'question_text' => htmlspecialchars($row['question_text']),
                'question_type' => $row['question_type'],
                'sort_order' => intval($row['sort_order']),
                'is_required' => boolval($row['is_required']),
                'options' => []
            ];
        }
        $stmt->close();

        if (!empty($question_ids)) {
            $placeholders = implode(',', array_fill(0, count($question_ids), '?'));
            $types = str_repeat('i', count($question_ids));
            $stmt = $conn->prepare("SELECT * FROM survey_options WHERE question_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
            $stmt->bind_param($types, ...$question_ids);
            $stmt->execute();
            $options_result = $stmt->get_result();
            $options_map = [];
            while ($row = $options_result->fetch_assoc()) {
                $qid = $row['question_id'];
                if (!isset($options_map[$qid])) {
                    $options_map[$qid] = [];
                }
                $options_map[$qid][] = [
                    'id' => intval($row['id']),
                    'option_text' => htmlspecialchars($row['option_text']),
                    'sort_order' => intval($row['sort_order'])
                ];
            }
            $stmt->close();

            foreach ($questions as &$q) {
                if (isset($options_map[$q['id']])) {
                    $q['options'] = $options_map[$q['id']];
                }
            }
            unset($q);
        }

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'id' => intval($survey['id']),
                'title' => htmlspecialchars($survey['title']),
                'description' => $survey['description'] ? htmlspecialchars($survey['description']) : null,
                'start_time' => $survey['start_time'],
                'end_time' => $survey['end_time'],
                'is_enabled' => boolval($survey['is_enabled']),
                'questions' => $questions,
                'created_at' => $survey['created_at'],
                'updated_at' => $survey['updated_at']
            ]
        ]);

    } elseif ($action === 'enabled') {
        $sql = "SELECT id, title, description, start_time, end_time 
                FROM surveys 
                WHERE is_enabled = 1 
                  AND (start_time IS NULL OR start_time <= NOW())
                  AND (end_time IS NULL OR end_time >= NOW())
                ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $surveys = [];
        while ($row = $result->fetch_assoc()) {
            $surveys[] = [
                'id' => intval($row['id']),
                'title' => htmlspecialchars($row['title']),
                'description' => $row['description'] ? htmlspecialchars($row['description']) : null,
                'start_time' => $row['start_time'],
                'end_time' => $row['end_time']
            ];
        }
        closeConnection($conn);

        jsonResponse(['success' => true, 'data' => $surveys]);

    } elseif ($action === 'toggle') {
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
        }

        $stmt = $conn->prepare("SELECT is_enabled FROM surveys WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷不存在'], 404);
        }

        $new_status = $row['is_enabled'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE surveys SET is_enabled = ? WHERE id = ?");
        $stmt->bind_param('ii', $new_status, $id);
        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => '状态更新成功', 'data' => ['is_enabled' => boolval($new_status)]]);
        } else {
            jsonResponse(['success' => false, 'message' => '状态更新失败'], 500);
        }
    }

} elseif ($method === 'POST') {
    $action = isset($data['action']) ? sanitize($data['action']) : 'create';

    if ($action === 'create' || $action === 'update') {
        $id = $action === 'update' ? (isset($data['id']) ? intval($data['id']) : 0) : 0;
        $title = isset($data['title']) ? sanitize($data['title']) : '';
        $description = isset($data['description']) ? sanitize($data['description']) : null;
        $start_time = isset($data['start_time']) && !empty($data['start_time']) ? sanitize($data['start_time']) : null;
        $end_time = isset($data['end_time']) && !empty($data['end_time']) ? sanitize($data['end_time']) : null;
        $is_enabled = isset($data['is_enabled']) ? intval($data['is_enabled']) : 1;
        $questions = isset($data['questions']) && is_array($data['questions']) ? $data['questions'] : [];

        if (empty($title)) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '请填写问卷标题'], 400);
        }

        if ($action === 'update' && $id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
        }

        $conn->begin_transaction();

        try {
            if ($action === 'create') {
                $stmt = $conn->prepare("INSERT INTO surveys (title, description, start_time, end_time, is_enabled) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssssi', $title, $description, $start_time, $end_time, $is_enabled);
                $stmt->execute();
                $survey_id = $conn->insert_id;
                $stmt->close();
            } else {
                $stmt = $conn->prepare("SELECT id FROM surveys WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if (!$stmt->get_result()->fetch_assoc()) {
                    $stmt->close();
                    throw new Exception('问卷不存在', 404);
                }
                $stmt->close();

                $survey_id = $id;
                $stmt = $conn->prepare("UPDATE surveys SET title = ?, description = ?, start_time = ?, end_time = ?, is_enabled = ? WHERE id = ?");
                $stmt->bind_param('ssssii', $title, $description, $start_time, $end_time, $is_enabled, $id);
                $stmt->execute();
                $stmt->close();

                $conn->query("DELETE FROM survey_options WHERE question_id IN (SELECT id FROM survey_questions WHERE survey_id = $survey_id)");
                $conn->query("DELETE FROM survey_questions WHERE survey_id = $survey_id");
            }

            foreach ($questions as $index => $q) {
                $q_text = isset($q['question_text']) ? sanitize($q['question_text']) : '';
                $q_type = isset($q['question_type']) ? sanitize($q['question_type']) : 'single';
                $q_sort = isset($q['sort_order']) ? intval($q['sort_order']) : $index;
                $q_required = isset($q['is_required']) ? intval($q['is_required']) : 1;
                $q_options = isset($q['options']) && is_array($q['options']) ? $q['options'] : [];

                if (empty($q_text)) {
                    throw new Exception('问题内容不能为空', 400);
                }

                $stmt = $conn->prepare("INSERT INTO survey_questions (survey_id, question_text, question_type, sort_order, is_required) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('issii', $survey_id, $q_text, $q_type, $q_sort, $q_required);
                $stmt->execute();
                $question_id = $conn->insert_id;
                $stmt->close();

                if ($q_type !== 'text') {
                    foreach ($q_options as $opt_index => $opt) {
                        $opt_text = isset($opt['option_text']) ? sanitize($opt['option_text']) : '';
                        $opt_sort = isset($opt['sort_order']) ? intval($opt['sort_order']) : $opt_index;

                        if (empty($opt_text)) {
                            throw new Exception('选项内容不能为空', 400);
                        }

                        $stmt = $conn->prepare("INSERT INTO survey_options (question_id, option_text, sort_order) VALUES (?, ?, ?)");
                        $stmt->bind_param('isi', $question_id, $opt_text, $opt_sort);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $conn->commit();
            closeConnection($conn);

            jsonResponse([
                'success' => true,
                'message' => $action === 'create' ? '问卷创建成功' : '问卷更新成功',
                'data' => ['id' => $survey_id]
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            closeConnection($conn);
            $code = $e->getCode() >= 400 ? $e->getCode() : 500;
            jsonResponse(['success' => false, 'message' => $e->getMessage()], $code);
        }

    } elseif ($action === 'delete') {
        $id = isset($data['id']) ? intval($data['id']) : 0;
        if ($id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
        }

        $stmt = $conn->prepare("SELECT id FROM surveys WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷不存在'], 404);
        }
        $stmt->close();

        $conn->query("UPDATE notices SET survey_id = NULL WHERE survey_id = $id");

        $stmt = $conn->prepare("DELETE FROM surveys WHERE id = ?");
        $stmt->bind_param('i', $id);
        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => '问卷删除成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '删除失败：' . $conn->error], 500);
        }

    } elseif ($action === 'bind_notice' || $action === 'unbind_notice') {
        $notice_id = isset($data['notice_id']) ? intval($data['notice_id']) : 0;
        $survey_id = $action === 'bind_notice' ? (isset($data['survey_id']) ? intval($data['survey_id']) : 0) : null;

        if ($notice_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的公告ID'], 400);
        }

        if ($action === 'bind_notice' && $survey_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
        }

        $stmt = $conn->prepare("SELECT id FROM notices WHERE id = ?");
        $stmt->bind_param('i', $notice_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            $stmt->close();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '公告不存在'], 404);
        }
        $stmt->close();

        if ($action === 'bind_notice') {
            $stmt = $conn->prepare("SELECT id FROM surveys WHERE id = ? AND is_enabled = 1");
            $stmt->bind_param('i', $survey_id);
            $stmt->execute();
            if (!$stmt->get_result()->fetch_assoc()) {
                $stmt->close();
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '问卷不存在或未启用'], 404);
            }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE notices SET survey_id = ? WHERE id = ?");
            $stmt->bind_param('ii', $survey_id, $notice_id);
        } else {
            $stmt = $conn->prepare("UPDATE notices SET survey_id = NULL WHERE id = ?");
            $stmt->bind_param('i', $notice_id);
        }

        $result = $stmt->execute();
        $stmt->close();
        closeConnection($conn);

        if ($result) {
            jsonResponse(['success' => true, 'message' => $action === 'bind_notice' ? '绑定成功' : '解绑成功']);
        } else {
            jsonResponse(['success' => false, 'message' => '操作失败'], 500);
        }
    }
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
