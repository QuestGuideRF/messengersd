<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
$userId = (int) $_SESSION['user_id'];
$saved = $db->t('saved_messages');
$messages = $db->t('messages');
$users = $db->t('users');
$sql = "SELECT sm.id AS saved_id, sm.created_at AS saved_at,
               m.id AS message_id, m.content, m.sender_id, m.receiver_id, m.created_at,
               u.full_name AS peer_full_name, u.username AS peer_username,
               CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS peer_id
        FROM {$saved} sm
        JOIN {$messages} m ON m.id = sm.message_id
        JOIN {$users} u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
        WHERE sm.user_id = ?
        ORDER BY sm.created_at DESC
        LIMIT 100";
$stmt = $db->executeQuery($sql, [$userId, $userId, $userId], 'iii');
if (is_string($stmt)) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $rows], JSON_UNESCAPED_UNICODE);