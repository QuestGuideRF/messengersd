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
if (isset($_POST['message_id'], $_POST['new_message'])) {
    $sender_id = $_SESSION['user_id'];
    $message_id = (int) $_POST['message_id'];
    $new_message = trim($_POST['new_message']);
    if (empty($new_message)) {
        $response['message'] = 'Сообщение не может быть пустым.';
    } else {
        $message = $query->select(
            'messages',
            '*',
            'id = ? AND sender_id = ?',
            [$message_id, $sender_id],
            'ii'
        );
        if ($message) {
            $data = ['content' => $new_message, 'is_edited' => 1];
            $update_result = $query->update(
                'messages',
                $data,
                'id = ?',
                [$message_id],
                'i'
            );
            if ($update_result > 0) {
                $response['status'] = 'success';
                $response['message'] = 'Сообщение изменено';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Не удалось изменить сообщение. Попробуйте позже.';
            }
        } else {
            $response['status'] = 'error';
            $response['message'] = 'Сообщение не найдено или у вас нет прав на редактирование.';
        }
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Некорректный запрос или не хватает параметров.';
}
echo json_encode($response);