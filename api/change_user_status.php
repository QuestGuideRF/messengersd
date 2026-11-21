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
    $response['message'] = 'User is not logged in';
    $response['data'] = [
        'loggedin' => false
    ];
    echo json_encode($response);
    exit;
}
include '../config.php';
$query = new Database();
if (isset($_POST['user_id'], $_POST['action'])) {
    $user_id = (int) $_POST['user_id'];
    $action = $_POST['action'];
    $current_user_id = $_SESSION['user_id'];
    $user_exists = $query->select('users', 'id', 'id = ?', [$user_id], 'i');
    if (empty($user_exists)) {
        $response['status'] = 'error';
        $response['message'] = 'Пользователь не найден.';
        echo json_encode($response);
        exit;
    }
    $existing_block = $query->select('block_users', '*', 'blocked_by = ? AND blocked_user = ?', [$current_user_id, $user_id], 'ii');
    if ($action == 'block' && !$existing_block) {
        $result = $query->insert('block_users', ['blocked_by' => $current_user_id, 'blocked_user' => $user_id]);
        if ($result > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Пользователь заблокирован.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Не удалось заблокировать пользователя. Попробуйте позже.';
        }
    } elseif ($action == 'unblock' && $existing_block) {
        $result = $query->delete('block_users', 'blocked_by = ? AND blocked_user = ?', [$current_user_id, $user_id], 'ii');
        if ($result > 0) {
            $response['status'] = 'success';
            $response['message'] = 'Пользователь разблокирован.';
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Не удалось разблокировать пользователя. Попробуйте позже.';
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Некорректное действие или пользователь уже в нужном состоянии.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Некорректный запрос. Нужны параметры user_id и action.';
}
echo json_encode($response);