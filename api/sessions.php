<?php
require_once __DIR__ . '/../app/session.php';
startAppSession();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$userId = (int) $_SESSION['user_id'];
$sid = session_id();
$table = $db->t('user_sessions');
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT id, session_id, ip, user_agent, created_at, last_seen, is_active
            FROM {$table}
            WHERE user_id = ?
            ORDER BY last_seen DESC";
    $stmt = $db->executeQuery($sql, [$userId], 'i');
    if (is_string($stmt)) {
        echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    foreach ($rows as &$r) {
        $r['is_current'] = ((string)$r['session_id'] === (string)$sid) ? 1 : 0;
    }
    unset($r);
    echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $rows], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    if ($action === 'logout_others') {
        $sql = "UPDATE {$table} SET is_active = 0 WHERE user_id = ? AND session_id != ?";
        $stmt = $db->executeQuery($sql, [$userId, $sid], 'is');
        if (is_string($stmt)) {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['status' => 'success', 'message' => 'Другие сессии отключены', 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($action === 'logout_session') {
        $target = isset($_POST['session_id']) ? (string)$_POST['session_id'] : '';
        if ($target === '' || $target === $sid) {
            echo json_encode(['status' => 'error', 'message' => 'Некорректная сессия', 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $sql = "UPDATE {$table} SET is_active = 0 WHERE user_id = ? AND session_id = ?";
        $stmt = $db->executeQuery($sql, [$userId, $target], 'is');
        if (is_string($stmt)) {
            echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
            exit;
        }
        echo json_encode(['status' => 'success', 'message' => 'Сессия отключена', 'data' => []], JSON_UNESCAPED_UNICODE);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'Некорректное действие', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(['status' => 'error', 'message' => 'Неверный метод', 'data' => []], JSON_UNESCAPED_UNICODE);