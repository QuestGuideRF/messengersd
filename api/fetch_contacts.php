<?php
require_once __DIR__ . '/../app/session.php';
startAppSession();
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
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$searchTermLike = "%" . $searchTerm . "%";
$showArchived = isset($_GET['archived']) && $_GET['archived'] == '1';
$usersTable = $query->t('users');
$messagesTable = $query->t('messages');
$pinnedTable = $query->t('pinned_chats');
$archivedTable = $query->t('archived_chats');
$sql = 'SELECT
            u.id AS user_id,
            u.full_name,
            u.username,
            u.profile_picture,
            MAX(m.created_at) AS last_message_time,
            MAX(pc.pinned_at) AS pinned_at,
            (
                SELECT m2.content
                FROM ' . $messagesTable . ' m2
                WHERE ((m2.receiver_id = u.id AND m2.sender_id = ?) OR (m2.receiver_id = ? AND m2.sender_id = u.id))
                ORDER BY m2.created_at DESC
                LIMIT 1
            ) AS last_message_content,
            (
                SELECT m2.sender_id
                FROM ' . $messagesTable . ' m2
                WHERE ((m2.receiver_id = u.id AND m2.sender_id = ?) OR (m2.receiver_id = ? AND m2.sender_id = u.id))
                ORDER BY m2.created_at DESC
                LIMIT 1
            ) AS last_message_sender_id,
            (
                SELECT m2.has_media
                FROM ' . $messagesTable . ' m2
                WHERE ((m2.receiver_id = u.id AND m2.sender_id = ?) OR (m2.receiver_id = ? AND m2.sender_id = u.id))
                ORDER BY m2.created_at DESC
                LIMIT 1
            ) AS last_message_has_media
        FROM
            ' . $usersTable . ' u
        LEFT JOIN
            ' . $messagesTable . ' m
            ON ( (m.receiver_id = u.id AND m.sender_id = ?) OR (m.receiver_id = ? AND m.sender_id = u.id) )
        LEFT JOIN
            ' . $pinnedTable . ' pc
            ON (pc.user_id = ? AND pc.peer_type = "user" AND pc.peer_id = u.id)
        LEFT JOIN
            ' . $archivedTable . ' ac
            ON (ac.user_id = ? AND ac.peer_type = "user" AND ac.peer_id = u.id)
        WHERE
            u.id != ? AND
            (u.full_name LIKE ? OR u.username LIKE ?)
            ' . ($showArchived ? ' AND ac.id IS NOT NULL ' : ' AND ac.id IS NULL ') . '
        GROUP BY
            u.id, u.full_name, u.username, u.profile_picture
        ORDER BY
            pinned_at DESC,
            last_message_time DESC,
            u.id ASC';
$stmt = $query->executeQuery(
    $sql,
    [
        $sender_id, $sender_id,
        $sender_id, $sender_id,
        $sender_id, $sender_id,
        $sender_id, $sender_id,
        $sender_id, $sender_id,
        $sender_id,
        $searchTermLike, $searchTermLike
    ],
    'iiiiiiiiiiiss'
);
if (is_string($stmt)) {
    $response['status'] = 'error';
    $response['message'] = 'Ошибка базы данных';
    echo json_encode($response);
    exit;
}
$allUsers = $stmt->get_result();
if ($allUsers && $allUsers->num_rows > 0) {
    $result = [];
    while ($user = $allUsers->fetch_assoc()) {
        $unreadMessagesQuery = '
            SELECT COUNT(*) AS unread_messages
            FROM ' . $messagesTable . '
            WHERE receiver_id = ? AND sender_id = ? AND status = "unread"';
        $unreadStmt = $query->executeQuery($unreadMessagesQuery, [$sender_id, $user['user_id']], 'ii');
        if (!is_string($unreadStmt)) {
            $unreadMessages = $unreadStmt->get_result()->fetch_assoc();
            $user['unread_messages'] = $unreadMessages['unread_messages'] ?? 0;
        } else {
            $user['unread_messages'] = 0;
        }
        $result[] = $user;
    }
    $response['status'] = 'success';
    $response['message'] = 'Контакты получены';
    $response['data'] = $result;
} else {
    $response['status'] = 'error';
    $response['message'] = 'Контакты не найдены';
}
echo json_encode($response);