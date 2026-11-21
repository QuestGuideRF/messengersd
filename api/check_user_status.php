<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}
header('Content-Type: application/json');
$response = [
    'status' => '',
    'message' => '',
    'data' => []
];
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['status'] = 'error';
    $response['message'] = 'Пользователь не авторизован';
    $response['data'] = [
        'loggedin' => false
    ];
    echo json_encode($response);
    exit;
}
include '../config.php';
$query = new Database();
if (isset($_GET['receiver_id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = (int) $_GET['receiver_id'];
    $blocked = $query->select('block_users', '*', 'blocked_by = ? AND blocked_user = ?', [$receiver_id, $sender_id], 'ii');
    if (!empty($blocked)) {
        $response['status'] = 'blocked';
        $response['message'] = 'Вы заблокированы этим пользователем.';
    } else {
        $response['status'] = 'unblocked';
        $response['message'] = 'Вы не заблокированы этим пользователем.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Не указан получатель. Передайте корректный receiver_id.';
}
echo json_encode($response);