<?php

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

if (!isset($_FILES['screenshot'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['screenshot'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds 5MB limit']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

if (!in_array($file['type'], $allowedTypes, true)) {
    echo json_encode(['success' => false, 'message' => 'File type not allowed']);
    exit;
}

$uploadDir = __DIR__ . '/uploads/feedback/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

$extension = $extensionMap[$file['type']] ?? 'jpg';
$filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit;
}

echo json_encode(['success' => true, 'url' => 'uploads/feedback/' . $filename]);
