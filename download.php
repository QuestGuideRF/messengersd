<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(403);
    exit('Доступ запрещён');
}
require_once __DIR__ . '/config.php';
$db = new Database();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    exit('Некорректный запрос');
}
$files = $db->select('media_files', '*', 'id = ?', [$id], 'i');
if (is_string($files) || empty($files)) {
    http_response_code(404);
    exit('Файл не найден');
}
$file = $files[0];
$msg = $db->select('messages', 'id, sender_id, receiver_id', 'id = ?', [(int)$file['message_id']], 'i');
if (is_string($msg) || empty($msg)) {
    http_response_code(404);
    exit('Файл не найден');
}
$m = $msg[0];
$userId = (int) $_SESSION['user_id'];
if ((int)$m['sender_id'] !== $userId && (int)$m['receiver_id'] !== $userId && !$db->currentUserIsAdmin()) {
    http_response_code(403);
    exit('Доступ запрещён');
}
$path = __DIR__ . '/' . ltrim((string)$file['path'], '/\\');
if (!is_file($path)) {
    http_response_code(404);
    exit('Файл не найден');
}
$mime = (string)($file['mime'] ?? 'application/octet-stream');
$name = (string)($file['original_name'] ?? 'file');
$size = (int)($file['size'] ?? filesize($path));
header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Content-Disposition: attachment; filename="' . rawurlencode($name) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);