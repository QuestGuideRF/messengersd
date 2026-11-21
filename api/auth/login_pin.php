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
if ($pin === '') {
    $response['message'] = 'Введите PIN-код';
    echo json_encode($response);
    exit;
}
if (!preg_match('/^\d{4}$/', $pin)) {
    $response['message'] = 'PIN должен состоять из 4 цифр';
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
    $response['message'] = 'Пользователь не найден';
    echo json_encode($response);
    exit;
}
$user = $users[0];
if (empty($user['pin_hash'])) {
    $response['message'] = 'PIN-код не задан. Создайте PIN при первом входе';
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
if (!password_verify($pin, (string) $user['pin_hash'])) {
    $attempts = (int) ($user['pin_failed_attempts'] ?? 0);
    $attempts++;
    $update = ['pin_failed_attempts' => (string) $attempts];
    if ($attempts >= $maxAttempts) {
        $update['pin_failed_attempts'] = '0';
        $update['pin_locked_until'] = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
    }
    $db->update('users', $update, 'id = ?', [(int) $user['id']], 'i');
    $response['message'] = $attempts >= $maxAttempts
        ? ('Неверный PIN-код. Вход временно заблокирован на ' . $lockMinutes . ' мин.')
        : 'Неверный PIN-код';
    echo json_encode($response);
    exit;
}
$db->update('users', ['pin_failed_attempts' => '0', 'pin_locked_until' => null], 'id = ?', [(int) $user['id']], 'i');
$_SESSION['loggedin'] = true;
$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['username'] = $user['username'];
$_SESSION['profile_picture'] = $user['profile_picture'];
setcookie('username', $username, time() + (86400 * 30), "/", "", true, true);
setcookie('session_token', session_id(), time() + (86400 * 30), "/", "", true, true);
$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
$sessionsTable = $db->t('user_sessions');
$db->executeQuery(
    "INSERT INTO {$sessionsTable} (user_id, session_id, ip, user_agent, created_at, last_seen, is_active)
     VALUES (?, ?, ?, ?, NOW(), NOW(), 1)
     ON DUPLICATE KEY UPDATE last_seen = NOW(), is_active = 1",
    [(int)$user['id'], session_id(), $ip, $ua],
    'isss'
);
$response = [
    'status' => 'success',
    'message' => 'Вход выполнен',
    'data' => [
        'loggedin' => true,
        'user_id' => $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'profile_picture' => $user['profile_picture']
    ]
];
echo json_encode($response);