<?php
require_once __DIR__ . '/../app/session.php';
startAppSession();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Неверный метод', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || empty($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$content = isset($_POST['content']) ? (string)$_POST['content'] : '';
$content = trim($content);
if ($content === '') {
    echo json_encode(['status' => 'error', 'message' => 'Пустое сообщение', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
if (mb_strlen($content, 'UTF-8') > 4000) {
    echo json_encode(['status' => 'error', 'message' => 'Слишком длинное сообщение', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$userId = (int)$_SESSION['user_id'];
$table = $db->t('saved_chat_messages');
$sql = "INSERT INTO {$table} (user_id, content, created_at) VALUES (?, ?, NOW())";
$stmt = $db->executeQuery($sql, [$userId, $content], 'is');
if (is_string($stmt)) {
    $msg = APP_DEBUG ? $stmt : 'Ошибка базы данных';
    echo json_encode(['status' => 'error', 'message' => $msg, 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['status' => 'success', 'message' => 'Отправлено', 'data' => []], JSON_UNESCAPED_UNICODE);