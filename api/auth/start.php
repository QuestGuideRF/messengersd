<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
require_once __DIR__ . '/../../config.php';
$db = new Database();
$response = ['status' => 'error', 'message' => 'Некорректный запрос', 'data' => []];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Неверный метод запроса';
    echo json_encode($response);
    exit;
}
$usernameRaw = isset($_POST['username']) ? trim((string)$_POST['username']) : '';
if ($usernameRaw === '') {
    $response['message'] = 'Введите никнейм';
    echo json_encode($response);
    exit;
}
$username = strtolower($usernameRaw);
if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
    $response['message'] = 'Никнейм может содержать только латинские буквы, цифры и подчёркивание (3–30 символов)';
    echo json_encode($response);
    exit;
}
$users = $db->select('users', '*', 'username = ?', [$username], 's');
if (is_string($users)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
if (empty($users)) {
    $response = [
        'status' => 'success',
        'message' => 'Новый пользователь',
        'data' => [
            'state' => 'create_pin',
            'username' => $username
        ]
    ];
    echo json_encode($response);
    exit;
}
$user = $users[0];
if (empty($user['pin_hash'])) {
    $response = [
        'status' => 'success',
        'message' => 'PIN не задан',
        'data' => [
            'state' => 'create_pin',
            'username' => $username
        ]
    ];
    echo json_encode($response);
    exit;
}
$response = [
    'status' => 'success',
    'message' => 'Пользователь найден',
    'data' => [
        'state' => 'enter_pin',
        'username' => $username
    ]
];
echo json_encode($response);