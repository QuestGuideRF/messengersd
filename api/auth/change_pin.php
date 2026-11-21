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
$currentPin = isset($_POST['current_pin']) ? trim((string)$_POST['current_pin']) : '';
$newPin = isset($_POST['new_pin']) ? trim((string)$_POST['new_pin']) : '';
$newPinConfirm = isset($_POST['new_pin_confirm']) ? trim((string)$_POST['new_pin_confirm']) : '';
if ($currentPin === '' || $newPin === '' || $newPinConfirm === '') {
    $response['message'] = 'Заполните все поля';
    echo json_encode($response);
    exit;
}
if (!preg_match('/^\d{4}$/', $currentPin)) {
    $response['message'] = 'Текущий PIN должен состоять из 4 цифр';
    echo json_encode($response);
    exit;
}
if (!preg_match('/^\d{4}$/', $newPin)) {
    $response['message'] = 'Новый PIN должен состоять из 4 цифр';
    echo json_encode($response);
    exit;
}
if ($newPin !== $newPinConfirm) {
    $response['message'] = 'PIN-коды не совпадают';
    echo json_encode($response);
    exit;
}
$userId = (int) $_SESSION['user_id'];
$users = $db->select('users', '*', 'id = ?', [$userId], 'i');
if (is_string($users) || empty($users)) {
    $response['message'] = 'Пользователь не найден';
    echo json_encode($response);
    exit;
}
$user = $users[0];
if (empty($user['pin_hash'])) {
    $response['message'] = 'PIN-код не задан';
    echo json_encode($response);
    exit;
}
$lockedUntil = $user['pin_locked_until'];
if (!empty($lockedUntil)) {
    $lockedTs = strtotime((string) $lockedUntil);
    if ($lockedTs !== false && $lockedTs > time()) {
        $mins = (int) ceil(($lockedTs - time()) / 60);
        $response['message'] = 'Слишком много попыток. Попробуйте снова через ' . $mins . ' мин.';
        echo json_encode($response);
        exit;
    }
}
$maxAttempts = 5;
$lockMinutes = 5;
if (!password_verify($currentPin, (string) $user['pin_hash'])) {
    $attempts = (int) ($user['pin_failed_attempts'] ?? 0);
    $attempts++;
    $update = ['pin_failed_attempts' => (string) $attempts];
    if ($attempts >= $maxAttempts) {
        $update['pin_failed_attempts'] = '0';
        $update['pin_locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
    }
    $db->update('users', $update, 'id = ?', [$userId], 'i');
    $response['message'] = $attempts >= $maxAttempts
        ? ('Неверный PIN-код. Доступ временно заблокирован на ' . $lockMinutes . ' мин.')
        : 'Неверный PIN-код';
    echo json_encode($response);
    exit;
}
$newHash = password_hash($newPin, PASSWORD_DEFAULT);
if ($newHash === false) {
    $response['message'] = 'Не удалось сохранить PIN. Попробуйте ещё раз';
    echo json_encode($response);
    exit;
}
$db->update('users', [
    'pin_hash' => $newHash,
    'pin_length' => (string) strlen($newPin),
    'pin_failed_attempts' => '0',
    'pin_locked_until' => null
], 'id = ?', [$userId], 'i');
$response = [
    'status' => 'success',
    'message' => 'PIN-код успешно изменён',
    'data' => []
];
echo json_encode($response);