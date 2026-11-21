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
if ($peerType !== 'user' || $peerId <= 0) {
    $response['message'] = 'Некорректные параметры';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$table = $db->t('user_typing');
$sql = "INSERT INTO {$table} (user_id, peer_type, peer_id, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE updated_at = NOW()";
$res = $db->executeQuery($sql, [$userId, $peerType, $peerId], 'isi');
if (is_string($res)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => []]);