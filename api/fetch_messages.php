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
if (isset($_POST['id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['id'];
    $isAdmin = $query->currentUserIsAdmin();
    $data = ['status' => 'read'];
    $update_result = $query->update(
        'messages',
        $data,
        'sender_id = ? AND receiver_id = ? AND status = "unread"',
        [$receiver_id, $sender_id],
        'ii'
    );
    $response['status'] = 'success';
    $response['message'] = 'Сообщения получены';
    $messagesTable = $query->t('messages');
    $reactionsTable = $query->t('message_reactions');
    $sql = "SELECT m.*, r.content AS reply_preview
            FROM {$messagesTable} m
            LEFT JOIN {$messagesTable} r ON r.id = m.reply_to_id
            WHERE ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))
            ORDER BY m.created_at";
    $stmt = $query->executeQuery($sql, [$sender_id, $receiver_id, $receiver_id, $sender_id], 'iiii');
    if (is_string($stmt)) {
        $response['status'] = 'error';
        $response['message'] = 'Ошибка базы данных';
        echo json_encode($response);
        exit;
    }
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    if (!empty($messages)) {
        $ids = array_map(fn($m) => (int)$m['id'], $messages);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $rxSql = "SELECT message_id, emoji, COUNT(*) AS cnt
                  FROM {$reactionsTable}
                  WHERE message_id IN ({$placeholders})
                  GROUP BY message_id, emoji";
        $rxStmt = $query->executeQuery($rxSql, $ids, $types);
        $rxRows = [];
        if (!is_string($rxStmt)) {
            $rxRows = $rxStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $mySql = "SELECT message_id, emoji
                  FROM {$reactionsTable}
                  WHERE user_id = ? AND message_id IN ({$placeholders})";
        $myStmt = $query->executeQuery($mySql, array_merge([$sender_id], $ids), 'i' . $types);
        $myRows = [];
        if (!is_string($myStmt)) {
            $myRows = $myStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $rxMap = [];
        foreach ($rxRows as $r) {
            $mid = (int)$r['message_id'];
            $rxMap[$mid] ??= [];
            $rxMap[$mid][$r['emoji']] = (int)$r['cnt'];
        }
        $myMap = [];
        foreach ($myRows as $r) {
            $mid = (int)$r['message_id'];
            $myMap[$mid] ??= [];
            $myMap[$mid][$r['emoji']] = true;
        }
        foreach ($messages as &$m) {
            $mid = (int)$m['id'];
            $m['reply_to_id'] = $m['reply_to_id'] ?? null;
            $m['is_admin_view'] = $isAdmin ? 1 : 0;
            $reactions = [];
            if (isset($rxMap[$mid])) {
                foreach ($rxMap[$mid] as $emoji => $cnt) {
                    $reactions[] = [
                        'emoji' => $emoji,
                        'count' => $cnt,
                        'mine' => !empty($myMap[$mid][$emoji]) ? 1 : 0
                    ];
                }
            }
            $m['reactions'] = $reactions;
        }
        unset($m);
        $mediaTable = $query->t('media_files');
        $mediaSql = "SELECT id, message_id, path, thumb_path, original_name, mime, size, is_image
                     FROM {$mediaTable}
                     WHERE message_id IN ({$placeholders})";
        $mediaStmt = $query->executeQuery($mediaSql, $ids, $types);
        $mediaRows = [];
        if (!is_string($mediaStmt)) {
            $mediaRows = $mediaStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        $mediaMap = [];
        foreach ($mediaRows as $r) {
            $mid = (int)$r['message_id'];
            $mediaMap[$mid] ??= [];
            $mediaMap[$mid][] = [
                'id' => (int)$r['id'],
                'path' => $r['path'],
                'thumb_path' => $r['thumb_path'],
                'original_name' => $r['original_name'],
                'mime' => $r['mime'],
                'size' => (int)$r['size'],
                'is_image' => (int)$r['is_image']
            ];
        }
        foreach ($messages as &$m) {
            $mid = (int)$m['id'];
            $m['attachments'] = $mediaMap[$mid] ?? [];
        }
        unset($m);
        $response['data'] = $messages;
    } else {
        $response['status'] = 'error';
        $response['message'] = 'Сообщений нет';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Не указан получатель';
}
echo json_encode($response);