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
$rows = $db->select('message_drafts', 'content, updated_at', 'user_id = ? AND peer_type = ? AND peer_id = ?', [$userId, $peerType, $peerId], 'isi');
if (is_string($rows)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$content = '';
$updatedAt = null;
if (!empty($rows)) {
    $content = (string) ($rows[0]['content'] ?? '');
    $updatedAt = $rows[0]['updated_at'] ?? null;
}
$response = ['status' => 'success', 'message' => 'OK', 'data' => ['content' => $content, 'updated_at' => $updatedAt]];
echo json_encode($response);