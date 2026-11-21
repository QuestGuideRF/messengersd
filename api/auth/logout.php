<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Content-Type: application/json');
require_once __DIR__ . '/../../app/session.php';
startAppSession();
require_once __DIR__ . '/../../config.php';
$db = new Database();
if (isset($_SESSION['user_id'])) {
    $table = $db->t('user_sessions');
    $db->executeQuery(
        "UPDATE {$table} SET is_active = 0 WHERE user_id = ? AND session_id = ?",
        [(int)$_SESSION['user_id'], session_id()],
        'is'
    );
}
session_unset();
session_destroy();
$cookies = ['username', 'session_token'];
foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
        if (PHP_VERSION_ID >= 70300) {
            setcookie($cookie, '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        } else {
            setcookie($cookie, '', time() - 3600, '/; samesite=Lax', '', $isHttps, true);
        }
    }
}
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
$response = [
    'status' => 'success',
    'message' => 'Вы вышли из профиля'
];
echo json_encode($response);
exit;