<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
$groupsTable = $db->t('groups');
$membersTable = $db->t('group_members');
$sql = "SELECT g.id, g.title,
               (SELECT COUNT(*) FROM {$membersTable} gm2 WHERE gm2.group_id = g.id) AS members_count
        FROM {$groupsTable} g
        JOIN {$membersTable} gm ON gm.group_id = g.id AND gm.user_id = ?
        ORDER BY g.created_at DESC";
$stmt = $db->executeQuery($sql, [$userId], 'i');
if (is_string($stmt)) {
    echo json_encode(['status' => 'error', 'message' => 'Ошибка базы данных', 'data' => []], JSON_UNESCAPED_UNICODE);
    exit;
}
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
echo json_encode(['status' => 'success', 'message' => 'OK', 'data' => $rows], JSON_UNESCAPED_UNICODE);