<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
$response = ['status' => 'error', 'message' => 'Некорректный запрос', 'data' => []];
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'Пользователь не авторизован';
    echo json_encode($response);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Неверный метод запроса';
    echo json_encode($response);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$messageId = isset($_POST['message_id']) ? (int)$_POST['message_id'] : 0;
$emoji = isset($_POST['emoji']) ? trim((string)$_POST['emoji']) : '';
if ($messageId <= 0 || $emoji === '') {
    $response['message'] = 'Не хватает параметров';
    echo json_encode($response);
    exit;
}
if (mb_strlen($emoji) > 8) {
    $response['message'] = 'Некорректная реакция';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$msg = $db->select('messages', 'id, sender_id, receiver_id', 'id = ?', [$messageId], 'i');
if (is_string($msg) || empty($msg)) {
    $response['message'] = 'Сообщение не найдено';
    echo json_encode($response);
    exit;
}
$m = $msg[0];
if ((int)$m['sender_id'] !== $userId && (int)$m['receiver_id'] !== $userId) {
    $response['message'] = 'Нет доступа';
    echo json_encode($response);
    exit;
}
$reactionsTable = $db->t('message_reactions');
$existsSql = "SELECT id FROM {$reactionsTable} WHERE message_id = ? AND user_id = ? AND emoji = ? LIMIT 1";
$exists = $db->executeQuery($existsSql, [$messageId, $userId, $emoji], 'iis');
if (is_string($exists)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$row = $exists->get_result()->fetch_assoc();
if ($row) {
    $delSql = "DELETE FROM {$reactionsTable} WHERE id = ?";
    $db->executeQuery($delSql, [(int)$row['id']], 'i');
    $response = ['status' => 'success', 'message' => 'Реакция убрана', 'data' => ['active' => false]];
    echo json_encode($response);
    exit;
}
$insSql = "INSERT INTO {$reactionsTable} (message_id, user_id, emoji, created_at) VALUES (?, ?, ?, NOW())";
$res = $db->executeQuery($insSql, [$messageId, $userId, $emoji], 'iis');
if (is_string($res)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$response = ['status' => 'success', 'message' => 'Реакция добавлена', 'data' => ['active' => true]];
echo json_encode($response);