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
$peerType = isset($_POST['peer_type']) ? trim((string)$_POST['peer_type']) : '';
$peerId = isset($_POST['peer_id']) ? (int)$_POST['peer_id'] : 0;
$content = isset($_POST['content']) ? (string)$_POST['content'] : '';
if ($peerType !== 'user') {
    $response['message'] = 'Некорректный тип чата';
    echo json_encode($response);
    exit;
}
if ($peerId <= 0) {
    $response['message'] = 'Некорректный идентификатор чата';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$table = $db->t('message_drafts');
$sql = "INSERT INTO {$table} (user_id, peer_type, peer_id, content, updated_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()";
$res = $db->executeQuery($sql, [$userId, $peerType, $peerId, $content], 'isis');
if (is_string($res)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$response = ['status' => 'success', 'message' => 'Сохранено', 'data' => []];
echo json_encode($response);