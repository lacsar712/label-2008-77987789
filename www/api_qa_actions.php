<?php
require_once 'config.php';
ensureQATables();

$method = $_SERVER['REQUEST_METHOD'];
$conn = getConnection();
$userIdentifier = getUserIdentifier();

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$action = isset($data['action']) ? sanitize($data['action']) : '';

if ($action === 'like' || $action === 'unlike') {
    $answer_id = isset($data['answer_id']) ? intval($data['answer_id']) : 0;

    if ($answer_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的回答ID'], 400);
    }

    $check_stmt = $conn->prepare("SELECT id FROM answers WHERE id = ?");
    $check_stmt->bind_param("i", $answer_id);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows === 0) {
        $check_stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '回答不存在'], 404);
    }
    $check_stmt->close();

    $conn->begin_transaction();
    try {
        if ($action === 'like') {
            $check_like = $conn->prepare("SELECT id FROM answer_likes WHERE answer_id = ? AND user_identifier = ?");
            $check_like->bind_param("is", $answer_id, $userIdentifier);
            $check_like->execute();
            if ($check_like->get_result()->num_rows > 0) {
                $check_like->close();
                $conn->rollback();
                closeConnection($conn);
                jsonResponse(['success' => false, 'message' => '已经点赞过了']);
            }
            $check_like->close();

            $stmt = $conn->prepare("INSERT INTO answer_likes (answer_id, user_identifier) VALUES (?, ?)");
            $stmt->bind_param("is", $answer_id, $userIdentifier);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("UPDATE answers SET likes = likes + 1 WHERE id = ?");
            $stmt->bind_param("i", $answer_id);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $stmt = $conn->prepare("SELECT likes FROM answers WHERE id = ?");
            $stmt->bind_param("i", $answer_id);
            $stmt->execute();
            $likes = $stmt->get_result()->fetch_assoc()['likes'];
            $stmt->close();
            closeConnection($conn);

            jsonResponse(['success' => true, 'message' => '点赞成功', 'data' => ['likes' => intval($likes), 'liked' => true]]);
        } else {
            $stmt = $conn->prepare("DELETE FROM answer_likes WHERE answer_id = ? AND user_identifier = ?");
            $stmt->bind_param("is", $answer_id, $userIdentifier);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted > 0) {
                $stmt = $conn->prepare("UPDATE answers SET likes = GREATEST(likes - 1, 0) WHERE id = ?");
                $stmt->bind_param("i", $answer_id);
                $stmt->execute();
                $stmt->close();
            }

            $conn->commit();

            $stmt = $conn->prepare("SELECT likes FROM answers WHERE id = ?");
            $stmt->bind_param("i", $answer_id);
            $stmt->execute();
            $likes = $stmt->get_result()->fetch_assoc()['likes'];
            $stmt->close();
            closeConnection($conn);

            jsonResponse(['success' => true, 'message' => '取消点赞成功', 'data' => ['likes' => intval($likes), 'liked' => false]]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()], 500);
    }
} elseif ($action === 'set_best') {
    $question_id = isset($data['question_id']) ? intval($data['question_id']) : 0;
    $answer_id = isset($data['answer_id']) ? intval($data['answer_id']) : 0;
    $operator = isset($data['operator']) ? sanitize($data['operator']) : '';

    if ($question_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的问题ID'], 400);
    }
    if ($answer_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的回答ID'], 400);
    }
    if (empty($operator)) {
        jsonResponse(['success' => false, 'message' => '请输入操作者名称进行权限校验'], 400);
    }

    $conn->begin_transaction();
    try {
        $q_stmt = $conn->prepare("SELECT asker FROM questions WHERE id = ?");
        $q_stmt->bind_param("i", $question_id);
        $q_stmt->execute();
        $question = $q_stmt->get_result()->fetch_assoc();
        $q_stmt->close();

        if (!$question) {
            $conn->rollback();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问题不存在'], 404);
        }

        if ($question['asker'] !== $operator) {
            $conn->rollback();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '只有提问者才能设置最佳答案'], 403);
        }

        $a_stmt = $conn->prepare("SELECT id, question_id FROM answers WHERE id = ? AND question_id = ?");
        $a_stmt->bind_param("ii", $answer_id, $question_id);
        $a_stmt->execute();
        if ($a_stmt->get_result()->num_rows === 0) {
            $a_stmt->close();
            $conn->rollback();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '回答不存在或不属于该问题'], 404);
        }
        $a_stmt->close();

        $stmt = $conn->prepare("UPDATE answers SET is_best = 0 WHERE question_id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE answers SET is_best = 1 WHERE id = ?");
        $stmt->bind_param("i", $answer_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE questions SET status = 'resolved', best_answer_id = ? WHERE id = ?");
        $stmt->bind_param("ii", $answer_id, $question_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        closeConnection($conn);

        jsonResponse(['success' => true, 'message' => '已设为最佳答案']);
    } catch (Exception $e) {
        $conn->rollback();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()], 500);
    }
} elseif ($action === 'unset_best') {
    $question_id = isset($data['question_id']) ? intval($data['question_id']) : 0;
    $operator = isset($data['operator']) ? sanitize($data['operator']) : '';

    if ($question_id <= 0) {
        jsonResponse(['success' => false, 'message' => '无效的问题ID'], 400);
    }
    if (empty($operator)) {
        jsonResponse(['success' => false, 'message' => '请输入操作者名称进行权限校验'], 400);
    }

    $conn->begin_transaction();
    try {
        $q_stmt = $conn->prepare("SELECT asker, best_answer_id FROM questions WHERE id = ?");
        $q_stmt->bind_param("i", $question_id);
        $q_stmt->execute();
        $question = $q_stmt->get_result()->fetch_assoc();
        $q_stmt->close();

        if (!$question) {
            $conn->rollback();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '问题不存在'], 404);
        }

        if ($question['asker'] !== $operator) {
            $conn->rollback();
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '只有提问者才能取消最佳答案'], 403);
        }

        if ($question['best_answer_id']) {
            $stmt = $conn->prepare("UPDATE answers SET is_best = 0 WHERE id = ?");
            $stmt->bind_param("i", $question['best_answer_id']);
            $stmt->execute();
            $stmt->close();
        }

        $stmt = $conn->prepare("UPDATE questions SET status = 'open', best_answer_id = NULL WHERE id = ?");
        $stmt->bind_param("i", $question_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        closeConnection($conn);

        jsonResponse(['success' => true, 'message' => '已取消最佳答案']);
    } catch (Exception $e) {
        $conn->rollback();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '操作失败：' . $e->getMessage()], 500);
    }
} else {
    closeConnection($conn);
    jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
}
