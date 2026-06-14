<?php
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'Only POST method is allowed'], 405);
}

if (!isset($_FILES['logo'])) {
    jsonResponse(['success' => false, 'message' => 'No file uploaded']);
}

$file = $_FILES['logo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
}

if ($file['size'] > 2 * 1024 * 1024) {
    jsonResponse(['success' => false, 'message' => 'File size exceeds 2MB limit']);
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
if (!in_array($file['type'], $allowedTypes, true)) {
    jsonResponse(['success' => false, 'message' => 'File type not allowed. Allowed: JPG, PNG, GIF, WEBP, SVG']);
}

$uploadDir = __DIR__ . '/uploads/templates/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$htaccess = $uploadDir . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Allow from all\n");
}

$extensionMap = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];
$extension = $extensionMap[$file['type']] ?? 'png';
$filename = 'logo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    jsonResponse(['success' => false, 'message' => 'Failed to save uploaded file']);
}

jsonResponse(['success' => true, 'url' => 'uploads/templates/' . $filename]);
