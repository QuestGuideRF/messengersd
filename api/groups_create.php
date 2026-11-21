<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Пользователь не авторизован', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Неверный метод', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$title = isset($_POST['title']) ? trim((string)$_POST['title']) : '';
$membersRaw = isset($_POST['members']) ? trim((string)$_POST['members']) : '';
if ($title === '' || mb_strlen($title) > 80) {
    echo json_encode(['status' => 'error', 'message' => 'Некорректное название', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$ownerId = (int) $_SESSION['user_id'];
$groupId = $db->insert('groups', [
    'title' => $title,
    'owner_id' => (string)$ownerId
]);
if (is_string($groupId) || !$groupId) {
    echo json_encode(['status' => 'error', 'message' => 'Не удалось создать группу', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$db->insert('group_members', [
    'group_id' => (string)$groupId,
    'user_id' => (string)$ownerId,
    'role' => 'owner'
]);
$usernames = [];
if ($membersRaw !== '') {
    foreach (preg_split('/[,\\s]+/', $membersRaw) as $u) {
        $u = strtolower(trim($u));
        if ($u !== '' && preg_match('/^[a-z0-9_]{3,30}$/', $u)) {
            $usernames[$u] = true;
        }
    }
}
if (!empty($usernames)) {
    $usersTable = $db->t('users');
    $in = implode(',', array_fill(0, count($usernames), '?'));
    $params = array_keys($usernames);
    $types = str_repeat('s', count($params));
    $stmt = $db->executeQuery("SELECT id, username FROM {$usersTable} WHERE username IN ({$in})", $params, $types);
    if (!is_string($stmt)) {
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as $r) {
            $uid = (int)$r['id'];
            if ($uid === $ownerId) continue;
            $db->executeQuery(
                "INSERT INTO " . $db->t('group_members') . " (group_id, user_id, role, joined_at)
                 VALUES (?, ?, 'member', NOW())
                 ON DUPLICATE KEY UPDATE role = role",
                [(int)$groupId, $uid],
                'ii'
            );
        }
    }
}
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => ['group_id' => (int)$groupId]], JSON_UNESCAPED_UNICODE);