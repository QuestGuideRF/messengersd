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
    $response['message'] = 'User is not logged in';
    $response['data'] = [
        'loggedin' => false
    ];
    echo json_encode($response);
    exit;
}
include '../config.php';
$query = new Database();
function log_upload_event(string $message, array $context = []): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
    if (!empty($context)) {
        $line .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }
    $line .= PHP_EOL;
    @file_put_contents(__DIR__ . '/../logs/upload.log', $line, FILE_APPEND);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['receiver_id'])) {
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'];
    $message_text = isset($_POST['content']) ? trim((string)$_POST['content']) : '';
    $reply_to_id = isset($_POST['reply_to_id']) && $_POST['reply_to_id'] !== '' ? (int) $_POST['reply_to_id'] : null;
    $hasAttachment = isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK;
    log_upload_event('incoming_send', [
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'has_file' => $hasAttachment,
        'file_error' => $_FILES['attachment']['error'] ?? null,
        'file_name' => $_FILES['attachment']['name'] ?? null,
        'file_size' => $_FILES['attachment']['size'] ?? null,
    ]);
    if ($message_text === '' && !$hasAttachment) {
        $response['status'] = 'error';
        $response['message'] = 'Введите текст сообщения или прикрепите файл.';
        echo json_encode($response);
        exit;
    }
    if ($reply_to_id !== null && $reply_to_id > 0) {
        $parent = $query->select('messages', 'id', 'id = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))', [$reply_to_id, $sender_id, $receiver_id, $receiver_id, $sender_id], 'iiiii');
        if (is_string($parent) || empty($parent)) {
            $reply_to_id = null;
        }
    }
    $data = [
        'sender_id' => $sender_id,
        'receiver_id' => $receiver_id,
        'content' => $message_text,
        'reply_to_id' => $reply_to_id,
        'has_link' => $message_text !== '' && preg_match('/https?:\\/\\//i', $message_text) ? 1 : 0,
        'has_media' => $hasAttachment ? 1 : 0,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $insertResult = $query->insert('messages', $data);
    if ($insertResult) {
        if ($hasAttachment) {
            $tmp = $_FILES['attachment']['tmp_name'];
            $origName = (string)($_FILES['attachment']['name'] ?? 'file');
            $size = (int)($_FILES['attachment']['size'] ?? 0);
            $maxBytes = 50 * 1024 * 1024;
            if ($size <= 0 || $size > $maxBytes) {
                log_upload_event('file_rejected', [
                    'message_id' => $insertResult,
                    'reason' => 'size',
                    'size' => $size,
                    'max' => $maxBytes
                ]);
            } else {
                $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
                $danger = ['php','phtml','phar','cgi','pl','asp','aspx','js','sh','bat','cmd','exe'];
                if ($ext === '' || in_array($ext, $danger, true)) {
                    $ext = 'bin';
                }
                $mime = mime_content_type($tmp) ?: 'application/octet-stream';
                $isImage = (int)preg_match('/^image\\//i', $mime);
                $safeBase = bin2hex(random_bytes(8));
                $fileName = $safeBase . '.' . $ext;
                $relPath = 'uploads/' . $fileName;
                $absPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $relPath;
                if (@move_uploaded_file($tmp, $absPath)) {
                    log_upload_event('file_moved', [
                        'message_id' => $insertResult,
                        'path' => $relPath,
                        'mime' => $mime,
                    ]);
                    $thumbRel = null;
                    if ($isImage && function_exists('imagecreatefromstring')) {
                        $thumbDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'thumbs';
                        if (!is_dir($thumbDir)) {
                            @mkdir($thumbDir, 0755, true);
                        }
                        $imgData = @file_get_contents($absPath);
                        $img = $imgData ? @imagecreatefromstring($imgData) : false;
                        if ($img) {
                            $w = imagesx($img);
                            $h = imagesy($img);
                            $maxW = 420;
                            $scale = ($w > $maxW) ? ($maxW / $w) : 1.0;
                            $nw = (int)max(1, floor($w * $scale));
                            $nh = (int)max(1, floor($h * $scale));
                            $thumb = imagecreatetruecolor($nw, $nh);
                            imagealphablending($thumb, false);
                            imagesavealpha($thumb, true);
                            imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
                            $thumbName = $safeBase . '_thumb.jpg';
                            $thumbRel = 'uploads/thumbs/' . $thumbName;
                            $thumbAbs = dirname(__DIR__) . DIRECTORY_SEPARATOR . $thumbRel;
                            if (!@imagejpeg($thumb, $thumbAbs, 82)) {
                                log_upload_event('thumb_write_failed', ['thumb' => $thumbAbs]);
                                $thumbRel = null;
                            }
                            imagedestroy($thumb);
                            imagedestroy($img);
                        }
                    }
                    $mediaInsert = $query->insert('media_files', [
                        'message_id' => $insertResult,
                        'user_id' => $sender_id,
                        'path' => $relPath,
                        'thumb_path' => $thumbRel,
                        'original_name' => $origName,
                        'mime' => $mime,
                        'size' => (string)$size,
                        'is_image' => (string)$isImage
                    ]);
                    if (is_string($mediaInsert)) {
                        log_upload_event('media_insert_error', ['error' => $mediaInsert]);
                    } else {
                        log_upload_event('media_insert_ok', ['media_id' => $mediaInsert]);
                    }
                } else {
                    log_upload_event('move_uploaded_file_failed', [
                        'tmp' => $tmp,
                        'target' => $absPath,
                    ]);
                }
            }
        }
        $new_message = [
            'id' => $insertResult,
            'content' => $message_text,
            'created_at' => $data['created_at']
        ];
        $response['status'] = 'success';
        $response['message'] = 'Сообщение отправлено';
        $response['data'] = $new_message;
    } else {
        log_upload_event('message_insert_error', ['error' => $insertResult]);
        $response['status'] = 'error';
        $response['message'] = 'Не удалось отправить сообщение. Попробуйте позже.';
    }
} else {
    $response['status'] = 'error';
    $response['message'] = 'Некорректный запрос.';
}
echo json_encode($response);