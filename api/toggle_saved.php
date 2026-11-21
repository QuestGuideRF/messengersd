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
require_once __DIR__ . '/../config.php';
$db = new Database();
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
if ($messageId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректное сообщение', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$msg = $db->select('messages', 'id, sender_id, receiver_id', 'id = ?', [$messageId], 'i');
if (is_string($msg) || empty($msg)) {
    echo json_encode(['status' => 'error', 'message' => 'Сообщение не найдено', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$m = $msg[0];
if ((int)$m['sender_id'] !== $userId && (int)$m['receiver_id'] !== $userId && !$db->currentUserIsAdmin()) {
    echo json_encode(['status' => 'error', 'message' => 'Нет доступа', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$savedTable = $db->t('saved_messages');
$exists = $db->executeQuery("SELECT id FROM {$savedTable} WHERE user_id = ? AND message_id = ? LIMIT 1", [$userId, $messageId], 'ii');
if (is_string($exists)) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$row = $exists->get_result()->fetch_assoc();
if ($row) {
    $db->executeQuery("DELETE FROM {$savedTable} WHERE id = ?", [(int)$row['id']], 'i');
    echo json_encode(['status' => 'success', 'message' => 'Удалено из сохранённых', 'data' => ['saved' => 0]], JSON_UNESCAPED_UNICODE);
    exit;
}
$db->executeQuery("INSERT INTO {$savedTable} (user_id, message_id, created_at) VALUES (?, ?, NOW())", [$userId, $messageId], 'ii');
echo json_encode(['status' => 'success', 'message' => 'Сохранено', 'data' => ['saved' => 1]], JSON_UNESCAPED_UNICODE);