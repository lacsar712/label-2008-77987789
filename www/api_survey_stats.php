<?php
require_once 'config.php';
ensureSurveyTables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();

if ($method === 'GET') {
    $action = isset($_GET['action']) ? sanitize($_GET['action']) : 'summary';
    $survey_id = isset($_GET['survey_id']) ? intval($_GET['survey_id']) : 0;

    if ($survey_id <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的问卷ID'], 400);
    }

    $stmt = $conn->prepare("SELECT * FROM surveys WHERE id = ?");
    $stmt->bind_param('i', $survey_id);
    $stmt->execute();
    $survey = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$survey) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '问卷不存在'], 404);
    }

    if ($action === 'summary') {
        $stmt = $conn->prepare("SELECT COUNT(DISTINCT visitor_id) as total_submissions FROM survey_answers WHERE survey_id = ?");
        $stmt->bind_param('i', $survey_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $total_submissions = intval($row['total_submissions']);
        $stmt->close();

        $stmt = $conn->prepare("SELECT id, question_text, question_type, sort_order FROM survey_questions WHERE survey_id = ? ORDER BY sort_order ASC, id ASC");
        $stmt->bind_param('i', $survey_id);
        $stmt->execute();
        $questions_result = $stmt->get_result();
        $questions = [];
        $question_ids = [];
        while ($row = $questions_result->fetch_assoc()) {
            $qid = intval($row['id']);
            $question_ids[] = $qid;
            $questions[] = [
                'id' => $qid,
                'question_text' => htmlspecialchars($row['question_text']),
                'question_type' => $row['question_type'],
                'sort_order' => intval($row['sort_order']),
                'options' => [],
                'stats' => null
            ];
        }
        $stmt->close();

        $options_map = [];
        $choice_question_ids = [];
        $text_question_ids = [];
        foreach ($questions as $q) {
            if ($q['question_type'] !== 'text') {
                $choice_question_ids[] = $q['id'];
            } else {
                $text_question_ids[] = $q['id'];
            }
        }

        if (!empty($choice_question_ids)) {
            $placeholders = implode(',', array_fill(0, count($choice_question_ids), '?'));
            $types = str_repeat('i', count($choice_question_ids));
            $stmt = $conn->prepare("SELECT id, question_id, option_text, sort_order FROM survey_options WHERE question_id IN ($placeholders) ORDER BY sort_order ASC, id ASC");
            $stmt->bind_param($types, ...$choice_question_ids);
            $stmt->execute();
            $options_result = $stmt->get_result();
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

            $all_option_ids = [];
            foreach ($options_map as $qid => $opts) {
                foreach ($opts as $opt) {
                    $all_option_ids[] = $opt['id'];
                }
            }

            $option_stats = [];
            if (!empty($all_option_ids)) {
                $opt_placeholders = implode(',', array_fill(0, count($all_option_ids), '?'));
                $opt_types = str_repeat('i', count($all_option_ids));
                $stmt = $conn->prepare("SELECT option_id, COUNT(*) as count FROM survey_answers WHERE survey_id = ? AND option_id IN ($opt_placeholders) GROUP BY option_id");
                array_unshift($all_option_ids, $survey_id);
                $opt_types = 'i' . $opt_types;
                $stmt->bind_param($opt_types, ...$all_option_ids);
                $stmt->execute();
                $stats_result = $stmt->get_result();
                while ($row = $stats_result->fetch_assoc()) {
                    $option_stats[intval($row['option_id'])] = intval($row['count']);
                }
                $stmt->close();
            }

            foreach ($questions as &$q) {
                if ($q['question_type'] !== 'text' && isset($options_map[$q['id']])) {
                    $q['options'] = $options_map[$q['id']];
                    $option_counts = [];
                    $total_for_q = 0;
                    foreach ($q['options'] as $opt) {
                        $count = isset($option_stats[$opt['id']]) ? $option_stats[$opt['id']] : 0;
                        $option_counts[] = [
                            'option_id' => $opt['id'],
                            'option_text' => $opt['option_text'],
                            'count' => $count,
                            'percentage' => $total_submissions > 0 ? round(($count / $total_submissions) * 100, 2) : 0
                        ];
                        $total_for_q += $count;
                    }
                    $q['stats'] = [
                        'total_responses' => $total_for_q,
                        'options' => $option_counts
                    ];
                }
            }
            unset($q);
        }

        if (!empty($text_question_ids)) {
            $text_placeholders = implode(',', array_fill(0, count($text_question_ids), '?'));
            $text_types = str_repeat('i', count($text_question_ids));
            $stmt = $conn->prepare("SELECT question_id, COUNT(*) as count FROM survey_answers WHERE survey_id = ? AND question_id IN ($text_placeholders) AND answer_text IS NOT NULL AND answer_text != '' GROUP BY question_id");
            array_unshift($text_question_ids, $survey_id);
            $text_types = 'i' . $text_types;
            $stmt->bind_param($text_types, ...$text_question_ids);
            $stmt->execute();
            $text_stats_result = $stmt->get_result();
            $text_counts = [];
            while ($row = $text_stats_result->fetch_assoc()) {
                $text_counts[intval($row['question_id'])] = intval($row['count']);
            }
            $stmt->close();

            foreach ($questions as &$q) {
                if ($q['question_type'] === 'text') {
                    $q['stats'] = [
                        'total_responses' => isset($text_counts[$q['id']]) ? $text_counts[$q['id']] : 0
                    ];
                }
            }
            unset($q);
        }

        $stmt = $conn->prepare("SELECT DATE(created_at) as submit_date, COUNT(DISTINCT visitor_id) as count FROM survey_answers WHERE survey_id = ? GROUP BY DATE(created_at) ORDER BY submit_date ASC");
        $stmt->bind_param('i', $survey_id);
        $stmt->execute();
        $trend_result = $stmt->get_result();
        $trend = [];
        while ($row = $trend_result->fetch_assoc()) {
            $trend[] = [
                'date' => $row['submit_date'],
                'count' => intval($row['count'])
            ];
        }
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
                    'end_time' => $survey['end_time'],
                    'is_enabled' => boolval($survey['is_enabled']),
                    'created_at' => $survey['created_at']
                ],
                'total_submissions' => $total_submissions,
                'questions' => $questions,
                'trend' => $trend
            ]
        ]);

    } elseif ($action === 'text_answers') {
        $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $offset = ($page - 1) * $per_page;

        if ($question_id <= 0) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的问题ID'], 400);
        }

        $stmt = $conn->prepare("SELECT id, question_text, question_type FROM survey_questions WHERE id = ? AND survey_id = ?");
        $stmt->bind_param('ii', $question_id, $survey_id);
        $stmt->execute();
        $question = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$question) {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问题不存在'], 404);
        }

        if ($question['question_type'] !== 'text') {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '该问题不是简答题'], 400);
        }

        $count_sql = "SELECT COUNT(*) as total FROM survey_answers WHERE survey_id = ? AND question_id = ? AND answer_text IS NOT NULL AND answer_text != ''";
        $stmt = $conn->prepare($count_sql);
        $stmt->bind_param('ii', $survey_id, $question_id);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $sql = "SELECT id, answer_text, created_at FROM survey_answers WHERE survey_id = ? AND question_id = ? AND answer_text IS NOT NULL AND answer_text != '' ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiii', $survey_id, $question_id, $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();

        $answers = [];
        while ($row = $result->fetch_assoc()) {
            $answers[] = [
                'id' => intval($row['id']),
                'answer_text' => htmlspecialchars($row['answer_text']),
                'created_at' => $row['created_at']
            ];
        }
        $stmt->close();
        closeConnection($conn);

        jsonResponse([
            'success' => true,
            'data' => [
                'question' => [
                    'id' => intval($question['id']),
                    'question_text' => htmlspecialchars($question['question_text'])
                ],
                'list' => $answers,
                'pagination' => [
                    'page' => $page,
                    'per_page' => $per_page,
                    'total' => intval($total),
                    'total_pages' => ceil($total / $per_page)
                ]
            ]
        ]);
    }
}

closeConnection($conn);
jsonResponse(['success' => false, 'message' => '不支持的请求方法'], 405);
