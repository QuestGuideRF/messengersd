<?php
session_start();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}
$response = ['status' => 'error', 'message' => 'Некорректный запрос', 'data' => []];
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    $response['message'] = 'Пользователь не авторизован';
    echo json_encode($response);
    exit;
}
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
if ($q === '' || mb_strlen($q) < 2) {
    $response['message'] = 'Введите минимум 2 символа';
    echo json_encode($response);
    exit;
}
require_once __DIR__ . '/../config.php';
$db = new Database();
$userId = (int) $_SESSION['user_id'];
$like = '%' . $q . '%';
$contacts = $db->select('users', 'id AS user_id, full_name, username', 'id != ? AND (full_name LIKE ? OR username LIKE ?)', [$userId, $like, $like], 'iss');
if (is_string($contacts)) {
    $contacts = [];
}
$messagesTable = $db->t('messages');
$usersTable = $db->t('users');
$sql = "SELECT
            m.id,
            CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS peer_id,
            u.username AS peer_username,
            u.full_name AS peer_full_name,
            m.content,
            m.created_at
        FROM {$messagesTable} m
        JOIN {$usersTable} u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
        WHERE (m.sender_id = ? OR m.receiver_id = ?)
          AND m.content LIKE ?
        ORDER BY m.created_at DESC
        LIMIT 50";
$stmt = $db->executeQuery($sql, [$userId, $userId, $userId, $userId, $like], 'iiiis');
$msgRows = [];
if (!is_string($stmt)) {
    $msgRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$messages = [];
foreach ($msgRows as $m) {
    $content = (string)($m['content'] ?? '');
    $snippet = mb_strlen($content) > 140 ? (mb_substr($content, 0, 140) . '…') : $content;
    $messages[] = [
        'id' => (int)$m['id'],
        'peer_id' => (int)$m['peer_id'],
        'peer_username' => (string)$m['peer_username'],
        'peer_full_name' => (string)$m['peer_full_name'],
        'snippet' => htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'),
        'created_at' => $m['created_at']
    ];
}
$response = [
    'status' => 'success',
    'message' => 'OK',
    'data' => [
        'contacts' => $contacts,
        'messages' => $messages
    ]
];
echo json_encode($response, JSON_UNESCAPED_UNICODE);