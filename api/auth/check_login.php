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
include '../../config.php';
$query = new Database();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    $response['status'] = 'success';
    $response['message'] = 'Пользователь авторизован';
    $response['data'] = [
        'loggedin' => true,
        'user_id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'],
        'username' => $_SESSION['username'],
        'profile_picture' => $_SESSION['profile_picture']
    ];
}
echo json_encode($response);