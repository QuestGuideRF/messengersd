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
$groupId = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
if ($groupId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректная группа', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$mem = $db->select('group_members', 'id', 'group_id = ? AND user_id = ?', [$groupId, $userId], 'ii');
if (is_string($mem) || empty($mem)) {
    echo json_encode(['status' => 'error', 'message' => 'Нет доступа', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$gm = $db->t('group_messages');
$u = $db->t('users');
$sql = "SELECT m.id, m.group_id, m.sender_id, m.content, m.created_at, us.full_name AS sender_name
        FROM {$gm} m
        JOIN {$u} us ON us.id = m.sender_id
        WHERE m.group_id = ?
        ORDER BY m.created_at";
$stmt = $db->executeQuery($sql, [$groupId], 'i');
if (is_string($stmt)) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $rows], JSON_UNESCAPED_UNICODE);