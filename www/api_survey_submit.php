<?php
require_once 'config.php';
ensureSurveyTables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();
$visitor_id = getUserIdentifier();

if ($method === 'GET') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : '';
    $survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;

    if ($survey_id <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
    }

    if ($action === 'check_submitted') {
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM survey_answers WHERE survey_id = ? AND visitor_id = ?");
        $stmt->bind_param('is', $survey_id, $visitor_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'has_submitted' => $row['cnt'] > 0
            ]
        ]);
    }

    if ($action === 'detail_for_answer') {
        $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ? AND is_enabled = 1");
        $stmt->bind_param('i', $survey_id);
        $stmt->execute();
        $survey = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$survey) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷不存在或未启用'], 404);
        }

        $now = date('Y-m-d H:i:s');
        if ($survey['start_time'] && $survey['start_time'] > $now) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷尚未开始'], 400);
        }
        if ($survey['end_time'] && $survey['end_time'] < $now) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问卷已结束'], 400);
        }

        $stmt = $conn->prepare("SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->bind_param('i', $survey_id);
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

        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM survey_answers WHERE survey_id = ? AND visitor_id = ?");
        $stmt->bind_param('is', $survey_id, $visitor_id);
        $stmt->execute();
        $submitted_row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'survey' => [
                    'id' => intval($survey['id']),
                    'title' => htmlspecialchars($survey['title']),
                    'description' => $survey['description'] ? htmlspecialchars($survey['description']) : null,
                    'start_time' => $survey['start_time'],
                    'end_time' => $survey['end_time']
                ],
                'questions' => $questions,
                'has_submitted' => $submitted_row['cnt'] > 0
            ]
        ]);
    }

    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $survey_id = isset($data['survey_id']) ? intval($data['survey_id']) : 0;
    $answers = isset($data['answers']) && is_array($data['answers']) ? $data['answers'] : [];

    if ($survey_id <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
    }

    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ? AND is_enabled = 1");
    $stmt->bind_param('i', $survey_id);
    $stmt->execute();
    $survey = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$survey) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问卷不存在或未启用'], 404);
    }

    $now = date('Y-m-d H:i:s');
    if ($survey['start_time'] && $survey['start_time'] > $now) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问卷尚未开始'], 400);
    }
    if ($survey['end_time'] && $survey['end_time'] < $now) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问卷已结束'], 400);
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM survey_answers WHERE survey_id = ? AND visitor_id = ?");
    $stmt->bind_param('is', $survey_id, $visitor_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row['cnt'] > 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '您已提交过该问卷'], 400);
    }

    $stmt = $conn->prepare("SELECT * FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
    $stmt->bind_param('i', $survey_id);
    $stmt->execute();
    $questions_result = $stmt->get_result();
    $questions = [];
    $question_map = [];
    while ($row = $questions_result->fetch_assoc()) {
        $qid = intval($row['id']);
        $questions[] = $row;
        $question_map[$qid] = $row;
    }
    $stmt->close();

    if (empty($questions)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问卷没有题目'], 400);
    }

    $answer_map = [];
    foreach ($answers as $a) {
        $qid = isset($a['question_id']) ? intval($a['question_id']) : 0;
        if ($qid > 0) {
            $answer_map[$qid] = $a;
        }
    }

    foreach ($questions as $q) {
        $qid = intval($q['id']);
        $q_type = $q['question_type'];
        $is_required = boolval($q['is_required']);
        $answer = isset($answer_map[$qid]) ? $answer_map[$qid] : null;

        if ($is_required) {
            if (!$answer) {
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '请完成所有必填题目'], 400);
            }
            if ($q_type === 'text') {
                $text = isset($answer['answer_text']) ? sanitize($answer['answer_text']) : '';
                if (empty($text)) {
                    closeConnection($conn);
                    jsonResponse(['success' => false, 'message' => '请完成所有必填题目'], 400);
                }
            } else {
                $option_ids = isset($answer['option_ids']) && is_array($answer['option_ids']) ? $answer['option_ids'] : [];
                if (empty($option_ids)) {
                    closeConnection($conn);
                    jsonResponse(['success' => false, 'message' => '请完成所有必填题目'], 400);
                }
            }
        }
    }

    $conn->begin_transaction();

    try {
        foreach ($questions as $q) {
            $qid = intval($q['id']);
            $q_type = $q['question_type'];
            $answer = isset($answer_map[$qid]) ? $answer_map[$qid] : null;

            if (!$answer) continue;

            if ($q_type === 'text') {
                $text = isset($answer['answer_text']) ? sanitize($answer['answer_text']) : '';
                if (!empty($text)) {
                    $stmt = $conn->prepare("INSERT INTO survey_answers (survey_id, question_id, visitor_id, answer_text) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param('iiss', $survey_id, $qid, $visitor_id, $text);
                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $option_ids = isset($answer['option_ids']) && is_array($answer['option_ids']) ? $answer['option_ids'] : [];
                foreach ($option_ids as $oid) {
                    $oid = intval($oid);
                    if ($oid > 0) {
                        $stmt = $conn->prepare("INSERT INTO survey_answers (survey_id, question_id, visitor_id, option_id) VALUES (?, ?, ?, ?)");
                        $stmt->bind_param('iisi', $survey_id, $qid, $visitor_id, $oid);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        $conn->commit();
        closeConnection($conn);

        jsonResponse(['success' => true, 'message' => '提交成功']);

    } catch (Exception $e) {
        $conn->rollback();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '提交失败：' . $e->getMessage()], 500);
    }
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
