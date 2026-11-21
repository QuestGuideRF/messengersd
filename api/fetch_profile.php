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
$sender_id = $_SESSION['user_id'];
$result = $query->select('users', '*', 'id = ?', [$sender_id], 'i');
$user = $result[0] ?? null;
if ($user) {
    $response = [
        'status' => 'success',
        'message' => 'Данные профиля получены',
        'data' => $user
    ];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $full_name = trim((string)($_POST['full_name'] ?? ''));
    $theme = isset($_POST['theme']) ? trim((string)$_POST['theme']) : null;
    $sound_enabled = isset($_POST['sound_enabled']) ? (int)$_POST['sound_enabled'] : null;
    $profile_picture = $user['profile_picture'];
    if ($full_name === '') {
        $response['status'] = 'error';
        $response['message'] = 'Введите имя';
        echo json_encode($response);
        exit;
    }
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['profile_picture']['tmp_name'];
        $fileName = $_FILES['profile_picture']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            $response['message'] = 'Неверный тип файла. Разрешены только JPG, JPEG, PNG, GIF.';
            echo json_encode($response);
            exit;
        }
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadDir = '../src/images/profile-picture/';
        $destPath = $uploadDir . $newFileName;
        if ($profile_picture && $profile_picture !== 'default.png') {
            $oldFilePath = $uploadDir . $profile_picture;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $profile_picture = $newFileName;
        } else {
            $response['message'] = 'Ошибка загрузки файла. Попробуйте ещё раз.';
            echo json_encode($response);
            exit;
        }
    }
    $updateData = ['full_name' => $full_name, 'profile_picture' => $profile_picture];
    if ($theme !== null) {
        if (!in_array($theme, ['dark', 'light', 'system'], true)) {
            $response['status'] = 'error';
            $response['message'] = 'Некорректная тема';
            echo json_encode($response);
            exit;
        }
        $updateData['theme'] = $theme;
    }
    if ($sound_enabled !== null) {
        $updateData['sound_enabled'] = $sound_enabled ? 1 : 0;
    }
    $updateResult = $query->update('users', $updateData, 'id = ?', [$sender_id], 'i');
    if ($updateResult > 0) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['profile_picture'] = $profile_picture;
        $response = [
            'status' => 'success',
            'message' => 'Профиль обновлён',
            'data' => $updateData
        ];
    } else {
        $response['message'] = 'Не удалось обновить профиль. Попробуйте позже.';
    }
}
echo json_encode($response);