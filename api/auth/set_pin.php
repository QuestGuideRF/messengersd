<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/session.php';
startAppSession();
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
$pin = isset($_POST['pin']) ? trim((string)$_POST['pin']) : '';
$pinConfirm = isset($_POST['pin_confirm']) ? trim((string)$_POST['pin_confirm']) : '';
if ($usernameRaw === '') {
    $response['message'] = 'Введите никнейм';
    echo json_encode($response);
    exit;
}
$username = strtolower($usernameRaw);
if (!preg_match('/^[a-z0-9_]{3,30}$/', $username)) {
    $response['message'] = 'Некорректный никнейм';
    echo json_encode($response);
    exit;
}
if ($pin === '' || $pinConfirm === '') {
    $response['message'] = 'Введите PIN-код и подтверждение';
    echo json_encode($response);
    exit;
}
if ($pin !== $pinConfirm) {
    $response['message'] = 'PIN-коды не совпадают';
    echo json_encode($response);
    exit;
}
if (!preg_match('/^\d{4}$/', $pin)) {
    $response['message'] = 'PIN должен состоять из 4 цифр';
    echo json_encode($response);
    exit;
}
$existing = $db->select('users', '*', 'username = ?', [$username], 's');
if (is_string($existing)) {
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
if (!empty($existing) && !empty($existing[0]['pin_hash'])) {
    $response['message'] = 'Пользователь уже существует. Введите PIN-код для входа';
    echo json_encode($response);
    exit;
}
$pinHash = password_hash($pin, PASSWORD_DEFAULT);
if ($pinHash === false) {
    $response['message'] = 'Не удалось сохранить PIN. Попробуйте ещё раз';
    echo json_encode($response);
    exit;
}
if (empty($existing)) {
    $userId = $db->insert('users', [
        'full_name' => $username,
        'username' => $username,
        'email' => null,
        'password' => null,
        'pin_hash' => $pinHash,
        'pin_length' => '4',
        'pin_failed_attempts' => '0',
        'pin_locked_until' => null,
        'profile_picture' => 'default.png'
    ]);
    if (is_string($userId) || empty($userId)) {
        $response['message'] = 'Не удалось создать профиль. Попробуйте позже';
        echo json_encode($response);
        exit;
    }
} else {
    $userId = (int) $existing[0]['id'];
    $updated = $db->update('users', [
        'pin_hash' => $pinHash,
        'pin_length' => '4',
        'pin_failed_attempts' => '0',
        'pin_locked_until' => null
    ], 'id = ?', [$userId], 'i');
    if (is_string($updated) || $updated < 0) {
        $response['message'] = 'Не удалось сохранить PIN. Попробуйте позже';
        echo json_encode($response);
        exit;
    }
}
$user = $db->select('users', '*', 'id = ?', [$userId], 'i');
if (is_string($user) || empty($user)) {
    $response['message'] = 'Профиль создан, но вход не выполнен. Попробуйте войти заново';
    echo json_encode($response);
    exit;
}
$u = $user[0];
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = $u['id'];
$_SESSION['full_name'] = $u['full_name'];
$_SESSION['username'] = $u['username'];
$_SESSION['profile_picture'] = $u['profile_picture'];
setcookie('username', $username, time() + (86400 * 30), "/", "", true, true);
setcookie('session_token', session_id(), time() + (86400 * 30), "/", "", true, true);
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
$sessionsTable = $db->t('user_sessions');
$db->executeQuery(
    "INSERT INTO {$sessionsTable} (user_id, session_id, ip, user_agent, created_at, last_seen, is_active)
     VALUES (?, ?, ?, ?, NOW(), NOW(), 1)
     ON DUPLICATE KEY UPDATE last_seen = NOW(), is_active = 1",
    [(int)$u['id'], session_id(), $ip, $ua],
    'isss'
);
$response = [
    'status' => 'success',
    'message' => 'PIN-код установлен. Вы вошли в профиль',
    'data' => [
        'loggedin' => true,
        'user_id' => $u['id'],
        'full_name' => $u['full_name'],
        'username' => $u['username'],
        'profile_picture' => $u['profile_picture']
    ]
];
echo json_encode($response);