<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ./login/");
    exit;
}
require_once __DIR__ . '/config.php';
$db = new Database();
$userId = (int) $_SESSION['user_id'];
$groupId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($groupId <= 0) {
    header("Location: ./groups.php");
    exit;
}
$member = $db->select('group_members', '*', 'group_id = ? AND user_id = ?', [$groupId, $userId], 'ii');
if (is_string($member) || empty($member)) {
    header("Location: ./groups.php");
    exit;
}
$g = $db->select('groups', '*', 'id = ?', [$groupId], 'i');
if (is_string($g) || empty($g)) {
    header("Location: ./groups.php");
    exit;
}
$group = $g[0];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группа | <?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" crossorigin="anonymous">
    <link rel="stylesheet" href="./src/css/style.css">
</head>
<body>
    <div class="container-fluid h-100">
        <div class="row justify-content-center h-100">
            <div class="col-md-8 col-xl-6 chat">
                <div class="card">
                    <div class="card-header msg_head">
                        <div class="d-flex bd-highlight" style="width:100%; align-items:center;">
                            <button type="button" class="btn btn-link p-0 mr-3 text-light" onclick="window.location.href='./groups.php';" title="К списку групп">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div class="user_info">
                                <span><?= htmlspecialchars($group['title'], ENT_QUOTES, 'UTF-8') ?></span>
                                <p id="chat-subtitle">группа</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body msg_card_body" id="messages-container"></div>
                    <div class="card-footer">
                        <div class="input-group" id="send_msg">
                            <textarea class="form-control type_msg" name="message" placeholder="Введите сообщение..."></textarea>
                            <div class="input-group-append">
                                <span class="input-group-text send_btn"><i class="fas fa-location-arrow"></i></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const groupId = <?= (int)$groupId ?>;
        const messagesContainer = document.getElementById('messages-container');
        function formatTime(ts) {
            if (!ts) return '';
            const d = new Date(String(ts).replace(' ', 'T'));
            if (isNaN(d.getTime())) return ts;
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        }
        function loadGroupMessages() {
            $.ajax({
                url: './api/group_fetch_messages.php',
                type: 'POST',
                data: { group_id: groupId },
                dataType: 'json',
                success: function(res) {
                    const list = (res && res.data) ? res.data : [];
                    messagesContainer.innerHTML = '';
                    if (!list.length) {
                        messagesContainer.innerHTML = `<div class="no-messages-container"><p class="no-messages">Сообщений пока нет.</p></div>`;
                        return;
                    }
                    list.forEach(m => {
                        const safe = $('<div/>').text(m.content || '').html();
                        messagesContainer.innerHTML += `
                            <div class="mb-3">
                                <div style="opacity:.7;font-size:12px;margin-bottom:4px;">${m.sender_name} • ${formatTime(m.created_at)}</div>
                                <div class="msg_cotainer" style="display:inline-block;">
                                    <div class="message-text">${safe}</div>
                                </div>
                            </div>
                        `;
                    });
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            });
        }
        loadGroupMessages();
        setInterval(loadGroupMessages, 1200);
        document.querySelector('.send_btn').addEventListener('click', function(e) {
            e.preventDefault();
            const input = document.querySelector('.type_msg');
            const text = (input.value || '').trim();
            if (!text) return;
            $.ajax({
                url: './api/group_send_message.php',
                method: 'POST',
                data: { group_id: groupId, content: text },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success') {
                        input.value = '';
                        loadGroupMessages();
                    }
                }
            });
        });
    </script>
</body>
</html>