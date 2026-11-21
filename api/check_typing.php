<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
require_once __DIR__ . '/../config.php';
$db = new Database();
$peerType = isset($_GET['peer_type']) ? trim((string)$_GET['peer_type']) : '';
$peerId = isset($_GET['peer_id']) ? (int)$_GET['peer_id'] : 0;
if ($peerType !== 'user' || $peerId <= 0) {
    $response['message'] = 'Некорректные параметры';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$table = $db->t('user_typing');
$sql = "SELECT 1
        FROM {$table}
        WHERE user_id = ? AND peer_type = 'user' AND peer_id = ?
          AND updated_at > (NOW() - INTERVAL 4 SECOND)
        LIMIT 1";
$stmt = $db->executeQuery($sql, [$peerId, $userId], 'ii');
if (is_string($stmt)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$isTyping = (bool) $stmt->get_result()->fetch_assoc();
echo json_encode([
    'status' => 'success',
    'message' => 'OK',
    'data' => ['is_typing' => $isTyping ? 1 : 0]
], JSON_UNESCAPED_UNICODE);