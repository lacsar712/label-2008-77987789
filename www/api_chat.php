<?php
require_once 'config.php';
ensureChatTables();

header('Content-Type: application/json; charset=UTF-8');

$conn = getConnection();
$userIdentifier = getUserIdentifier();

define('CHAT_ADMIN_TOKEN', 'chat_admin_2024');
define('CHAT_HEARTBEAT_TIMEOUT', 60);

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data)) {
    $data = $_POST;
}

$action = isset($data['action']) ? $data['action'] : '';
$adminToken = isset($data['admin_token']) ? $data['admin_token'] : '';
$isAdmin = ($adminToken === CHAT_ADMIN_TOKEN);

switch ($action) {
    case 'rooms':
        handleRooms($conn);
        break;
    case 'join':
        handleJoin($conn, $userIdentifier, $data, $isAdmin);
        break;
    case 'leave':
        handleLeave($conn, $userIdentifier, $data);
        break;
    case 'messages':
        handleMessages($conn, $data);
        break;
    case 'send':
        handleSend($conn, $userIdentifier, $data, $isAdmin);
        break;
    case 'upload_image':
        handleUploadImage($conn, $userIdentifier, $data, $isAdmin);
        break;
    case 'heartbeat':
        handleHeartbeat($conn, $userIdentifier, $data, $isAdmin);
        break;
    case 'online':
        handleOnline($conn, $data);
        break;
    default:
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的操作'], 400);
}

function handleRooms($conn) {
    $result = $conn->query("SELECT id, name, description, is_default FROM chat_rooms ORDER BY is_default DESC, id ASC");
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => $rooms]);
}

function handleJoin($conn, $userIdentifier, $data, $isAdmin) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
    $nickname = isset($data['nickname']) ? trim($data['nickname']) : '';

    if ($roomId <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
    }
    if (empty($nickname) || mb_strlen($nickname, 'UTF-8') > 20) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '昵称不能为空且不超过20字符'], 400);
    }

    $stmt = $conn->prepare("SELECT id FROM chat_rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '房间不存在'], 404);
    }
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO chat_users_online (room_id, nickname, user_identifier, is_admin) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE nickname = VALUES(nickname), is_admin = VALUES(is_admin), last_heartbeat = CURRENT_TIMESTAMP");
    $adminFlag = $isAdmin ? 1 : 0;
    $stmt->bind_param("issi", $roomId, $nickname, $userIdentifier, $adminFlag);
    $stmt->execute();
    $stmt->close();

    $systemMsg = $nickname . ' 加入了房间';
    $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, nickname, user_identifier, message_type, content, is_admin) VALUES (?, '', '', 'system', ?, 0)");
    $stmt->bind_param("is", $roomId, $systemMsg);
    $stmt->execute();
    $stmt->close();

    cleanStaleUsers($conn, $roomId);

    closeConnection($conn);
    jsonResponse(['success' => true, 'message' => '已加入房间']);
}

function handleLeave($conn, $userIdentifier, $data) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;

    if ($roomId <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
    }

    $stmt = $conn->prepare("SELECT nickname FROM chat_users_online WHERE room_id = ? AND user_identifier = ?");
    $stmt->bind_param("is", $roomId, $userIdentifier);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($user) {
        $systemMsg = $user['nickname'] . ' 离开了房间';
        $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, nickname, user_identifier, message_type, content, is_admin) VALUES (?, '', '', 'system', ?, 0)");
        $stmt->bind_param("is", $roomId, $systemMsg);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare("DELETE FROM chat_users_online WHERE room_id = ? AND user_identifier = ?");
    $stmt->bind_param("is", $roomId, $userIdentifier);
    $stmt->execute();
    $stmt->close();

    closeConnection($conn);
    jsonResponse(['success' => true, 'message' => '已离开房间']);
}

function handleMessages($conn, $data) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
    $lastId = isset($data['last_id']) ? intval($data['last_id']) : 0;
    $limit = isset($data['limit']) ? min(intval($data['limit']), 100) : 50;

    if ($roomId <= 0) {
        $defaultResult = $conn->query("SELECT id FROM chat_rooms WHERE is_default = 1 LIMIT 1");
        $defaultRoom = $defaultResult->fetch_assoc();
        if ($defaultRoom) {
            $roomId = $defaultRoom['id'];
        } else {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
        }
    }

    if ($lastId > 0) {
        $stmt = $conn->prepare("SELECT id, room_id, nickname, message_type, content, mention_nickname, is_admin, created_at FROM chat_messages WHERE room_id = ? AND id > ? ORDER BY id ASC LIMIT ?");
        $stmt->bind_param("iii", $roomId, $lastId, $limit);
    } else {
        $stmt = $conn->prepare("SELECT id, room_id, nickname, message_type, content, mention_nickname, is_admin, created_at FROM chat_messages WHERE room_id = ? ORDER BY id DESC LIMIT ?");
        $roomIdParam = $roomId;
        $stmt->bind_param("ii", $roomIdParam, $limit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }
    $stmt->close();

    if ($lastId === 0) {
        $messages = array_reverse($messages);
    }

    $maxId = 0;
    if (!empty($messages)) {
        $maxId = intval($messages[count($messages) - 1]['id']);
    }

    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => ['messages' => $messages, 'last_id' => $maxId]]);
}

function handleSend($conn, $userIdentifier, $data, $isAdmin) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
    $nickname = isset($data['nickname']) ? trim($data['nickname']) : '';
    $content = isset($data['content']) ? trim($data['content']) : '';
    $mentionNickname = isset($data['mention_nickname']) ? trim($data['mention_nickname']) : null;

    if ($roomId <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
    }
    if (empty($nickname)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '昵称不能为空'], 400);
    }
    if (empty($content)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '消息内容不能为空'], 400);
    }
    if (mb_strlen($content, 'UTF-8') > 2000) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '消息内容不超过2000字符'], 400);
    }

    $stmt = $conn->prepare("SELECT id FROM chat_rooms WHERE id = ?");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '房间不存在'], 404);
    }
    $stmt->close();

    $adminFlag = $isAdmin ? 1 : 0;
    $contentEscaped = $conn->real_escape_string($content);
    $nicknameEscaped = $conn->real_escape_string($nickname);
    $mentionEscaped = $mentionNickname ? $conn->real_escape_string($mentionNickname) : null;

    if ($mentionEscaped) {
        $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, nickname, user_identifier, message_type, content, mention_nickname, is_admin) VALUES (?, ?, ?, 'text', ?, ?, ?)");
        $stmt->bind_param("issssi", $roomId, $nicknameEscaped, $userIdentifier, $contentEscaped, $mentionEscaped, $adminFlag);
    } else {
        $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, nickname, user_identifier, message_type, content, is_admin) VALUES (?, ?, ?, 'text', ?, ?)");
        $stmt->bind_param("isssi", $roomId, $nicknameEscaped, $userIdentifier, $contentEscaped, $adminFlag);
    }
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO chat_users_online (room_id, nickname, user_identifier, is_admin, last_heartbeat) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE last_heartbeat = CURRENT_TIMESTAMP, is_admin = VALUES(is_admin)");
    $stmt->bind_param("issi", $roomId, $nicknameEscaped, $userIdentifier, $adminFlag);
    $stmt->execute();
    $stmt->close();

    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => ['id' => $msgId]]);
}

function handleUploadImage($conn, $userIdentifier, $data, $isAdmin) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;
    $nickname = isset($data['nickname']) ? trim($data['nickname']) : '';

    if ($roomId <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
    }
    if (empty($nickname)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '昵称不能为空'], 400);
    }
    if (!isset($_FILES['image'])) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '请选择图片文件'], 400);
    }

    $file = $_FILES['image'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '上传出错'], 400);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '图片大小不能超过5MB'], 400);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes, true)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '仅支持 JPG/PNG/GIF/WebP 格式'], 400);
    }

    $uploadDir = __DIR__ . '/uploads/chat/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $extensionMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    $extension = $extensionMap[$file['type']] ?? 'jpg';
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '文件保存失败'], 500);
    }

    $imageUrl = 'uploads/chat/' . $filename;
    $adminFlag = $isAdmin ? 1 : 0;
    $nicknameEscaped = $conn->real_escape_string($nickname);

    $stmt = $conn->prepare("INSERT INTO chat_messages (room_id, nickname, user_identifier, message_type, content, is_admin) VALUES (?, ?, ?, 'image', ?, ?)");
    $stmt->bind_param("isssi", $roomId, $nicknameEscaped, $userIdentifier, $imageUrl, $adminFlag);
    $stmt->execute();
    $msgId = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO chat_users_online (room_id, nickname, user_identifier, is_admin, last_heartbeat) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE last_heartbeat = CURRENT_TIMESTAMP");
    $stmt->bind_param("issi", $roomId, $nicknameEscaped, $userIdentifier, $adminFlag);
    $stmt->execute();
    $stmt->close();

    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => ['id' => $msgId, 'url' => $imageUrl]]);
}

function handleHeartbeat($conn, $userIdentifier, $data, $isAdmin) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;

    if ($roomId <= 0) {
        closeConnection($conn);
        jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
    }

    $adminFlag = $isAdmin ? 1 : 0;
    $stmt = $conn->prepare("UPDATE chat_users_online SET last_heartbeat = CURRENT_TIMESTAMP, is_admin = ? WHERE room_id = ? AND user_identifier = ?");
    $stmt->bind_param("iis", $adminFlag, $roomId, $userIdentifier);
    $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();

    cleanStaleUsers($conn, $roomId);

    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => ['active' => $affected > 0]]);
}

function handleOnline($conn, $data) {
    $roomId = isset($data['room_id']) ? intval($data['room_id']) : 0;

    if ($roomId <= 0) {
        $defaultResult = $conn->query("SELECT id FROM chat_rooms WHERE is_default = 1 LIMIT 1");
        $defaultRoom = $defaultResult->fetch_assoc();
        if ($defaultRoom) {
            $roomId = $defaultRoom['id'];
        } else {
            closeConnection($conn);
            jsonResponse(['success' => false, 'message' => '无效的房间ID'], 400);
        }
    }

    cleanStaleUsers($conn, $roomId);

    $stmt = $conn->prepare("SELECT nickname, is_admin, joined_at FROM chat_users_online WHERE room_id = ? ORDER BY is_admin DESC, joined_at ASC");
    $stmt->bind_param("i", $roomId);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $stmt->close();

    closeConnection($conn);
    jsonResponse(['success' => true, 'data' => ['count' => count($users), 'users' => $users]]);
}

function cleanStaleUsers($conn, $roomId) {
    $timeout = CHAT_HEARTBEAT_TIMEOUT;
    $stmt = $conn->prepare("DELETE FROM chat_users_online WHERE room_id = ? AND last_heartbeat < DATE_SUB(NOW(), INTERVAL ? SECOND)");
    $stmt->bind_param("ii", $roomId, $timeout);
    $stmt->execute();
    $stmt->close();
}
