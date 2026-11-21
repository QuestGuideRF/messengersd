<?php
require_once __DIR__ . '/../app/session.php';
startAppSession();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$userId = (int)$_SESSION['user_id'];
$table = $db->t('saved_chat_messages');
$sql = "SELECT id, user_id, content, created_at
        FROM {$table}
        WHERE user_id = ?
        ORDER BY id ASC
        LIMIT 500";
$stmt = $db->executeQuery($sql, [$userId], 'i');
if (is_string($stmt)) {
    $msg = APP_DEBUG ? $stmt : 'Ошибка базы данных';
    echo json_encode(['status' => 'error', 'message' => $msg, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $rows], JSON_UNESCAPED_UNICODE);