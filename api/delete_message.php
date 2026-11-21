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
if (isset($_POST['message_id'])) {
    $message_id = (int) $_POST['message_id'];
    $scope = isset($_POST['scope']) ? $_POST['scope'] : 'me';
    $rows = $query->select('messages', '*', 'id = ?', [$message_id], 'i');
    if (!empty($rows)) {
        $currentUserId = (int) $_SESSION['user_id'];
        $msg = $rows[0];
        $isSender = (int) $msg['sender_id'] === $currentUserId;
        $isReceiver = (int) $msg['receiver_id'] === $currentUserId;
        if (!$isSender && !$isReceiver) {
            $response['status'] = 'error';
            $response['message'] = 'Вы не можете удалять это сообщение.';
        } else {
            $data = [];
            if ($scope === 'all' && $isSender) {
                $data['deleted_for_sender'] = 1;
                $data['deleted_for_receiver'] = 1;
            } elseif ($scope === 'me') {
                if ($isSender) {
                    $data['deleted_for_sender'] = 1;
                } elseif ($isReceiver) {
                    $data['deleted_for_receiver'] = 1;
                }
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Некорректный тип удаления.';
                echo json_encode($response);
                exit;
            }
            $update_result = $query->update(
                'messages',
                $data,
                'id = ?',
                [$message_id],
                'i'
            );
            if (!is_string($update_result) && $update_result >= 0) {
                $response['status'] = 'success';
                $response['message'] = 'Сообщение удалено';
            } else {
                $response['status'] = 'error';
                $response['message'] = 'Не удалось удалить сообщение. Попробуйте позже.';
            }
        }
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Сообщение не найдено.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Не указан идентификатор сообщения.';
}
echo json_encode($response);