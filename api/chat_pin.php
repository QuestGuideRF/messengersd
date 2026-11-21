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
require_once __DIR__ . '/../config.php';
$db = new Database();
$peerType = isset($_POST['peer_type']) ? trim((string)$_POST['peer_type']) : '';
$peerId = isset($_POST['peer_id']) ? (int)$_POST['peer_id'] : 0;
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if (!in_array($peerType, ['user','group'], true) || $peerId <= 0) {
    $response['message'] = 'Некорректные параметры';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$table = $db->t('pinned_chats');
if ($action === 'pin') {
    $sql = "INSERT INTO {$table} (user_id, peer_type, peer_id, pinned_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE pinned_at = NOW()";
    $res = $db->executeQuery($sql, [$userId, $peerType, $peerId], 'isi');
    if (is_string($res)) {
        $response['message'] = 'Ошибка базы данных';
        echo json_encode($response);
        exit;
    }
    echo json_encode(['status' => 'success', 'message' => 'Чат закреплён', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($action === 'unpin') {
    $sql = "DELETE FROM {$table} WHERE user_id = ? AND peer_type = ? AND peer_id = ?";
    $res = $db->executeQuery($sql, [$userId, $peerType, $peerId], 'isi');
    if (is_string($res)) {
        $response['message'] = 'Ошибка базы данных';
        echo json_encode($response);
        exit;
    }
    echo json_encode(['status' => 'success', 'message' => 'Чат откреплён', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$response['message'] = 'Некорректное действие';
echo json_encode($response);