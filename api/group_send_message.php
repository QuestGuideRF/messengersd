<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Неверный метод', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
$content = isset($_POST['content']) ? trim((string)$_POST['content']) : '';
if ($groupId <= 0 || $content === '') {
    echo json_encode(['status' => 'error', 'message' => 'Некорректные параметры', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$mem = $db->select('group_members', 'id', 'group_id = ? AND user_id = ?', [$groupId, $userId], 'ii');
if (is_string($mem) || empty($mem)) {
    echo json_encode(['status' => 'error', 'message' => 'Нет доступа', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$id = $db->insert('group_messages', [
    'group_id' => (string)$groupId,
    'sender_id' => (string)$userId,
    'content' => $content,
    'has_link' => preg_match('/https?:\\/\\//i', $content) ? 1 : 0,
    'created_at' => date('Y-m-d H:i:s')
]);
if (is_string($id) || !$id) {
    echo json_encode(['status' => 'error', 'message' => 'Не удалось отправить', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => ['id' => (int)$id]], JSON_UNESCAPED_UNICODE);