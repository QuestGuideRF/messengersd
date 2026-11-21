<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || empty($_SESSION['user_id'])) {
    header("Location: ./login/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сохранённые</title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
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
                            <button type="button" class="btn btn-link p-0 mr-3 text-light" onclick="window.location.href='./';" title="К списку чатов">
                                <i class="fas fa-arrow-left"></i>
                            </button>
                            <div class="img_cont">
                                <div class="rounded-circle user_img" style="display:flex;align-items:center;justify-content:center;background:rgba(110,231,255,.18);border:1px solid rgba(110,231,255,.25);">
                                    <i class="fas fa-bookmark" style="color: rgba(245,247,255,.9);"></i>
                                </div>
                            </div>
                            <div class="user_info">
                                <span>Сохранённые</span>
                                <p style="margin:0; opacity:.75;">Сообщения самому себе</p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body msg_card_body" id="messages-container"></div>
                    <div class="card-footer">
                        <div class="input-group" id="send_saved">
                            <textarea class="form-control type_msg" name="message" id="savedMessage" placeholder="Введите сообщение..."></textarea>
                            <div class="input-group-append">
                                <button type="button" class="input-group-text send_btn" id="sendSavedBtn" title="Отправить">
                                    <i class="fas fa-location-arrow"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        const messagesContainer = document.getElementById('messages-container');
        const savedMessage = document.getElementById('savedMessage');
        const sendSavedBtn = document.getElementById('sendSavedBtn');
        function formatTime(ts) {
            if (!ts) return '';
            const d = new Date(String(ts).replace(' ', 'T'));
            if (isNaN(d.getTime())) return '';
            return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
        }
        function formatDateLabel(ts) {
            const d = new Date(String(ts).replace(' ', 'T'));
            if (isNaN(d.getTime())) return '';
            const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }
        function render(rows) {
            messagesContainer.innerHTML = '';
            if (!rows || !rows.length) {
                messagesContainer.innerHTML = `
                    <div class="no-messages-container">
                        <i class="fas fa-bookmark fa-3x"></i>
                        <p class="no-messages">Пока пусто.</p>
                    </div>`;
                return;
            }
            let lastDate = '';
            rows.forEach((row) => {
                const createdAt = row.created_at || '';
                const datePart = createdAt.split(' ')[0] || '';
                if (datePart && datePart !== lastDate) {
                    lastDate = datePart;
                    messagesContainer.innerHTML += `<div class="day-separator"><span>${formatDateLabel(createdAt)}</span></div>`;
                }
                const safeContent = $('<div/>').text(row.content || '').html();
                const time = formatTime(createdAt);
                messagesContainer.innerHTML += `
                    <div class="d-flex justify-content-end mb-4" style="margin-left:15px">
                        <div style="display:flex;justify-content:center;align-items:center">
                            <div class="msg_cotainer_send">
                                <div class="message-text">${safeContent}</div>
                                <span class="msg_time_send">${time}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            try {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            } catch {}
        }
        function loadSavedChat() {
            fetch('./api/saved_chat_fetch.php')
                .then((response) => response.json())
                .then((result) => {
                    if (result.status === 'success') {
                        render(result.data || []);
                        return;
                    }
                    render([]);
                })
                .catch(() => render([]));
        }
        function sendSaved() {
            const text = (savedMessage.value || '').trim();
            if (!text) return;
            const formData = new FormData();
            formData.append('content', text);
            fetch('./api/saved_chat_send.php', {
                method: 'POST',
                body: formData
            })
                .then((response) => response.json())
                .then((result) => {
                    if (result.status === 'success') {
                        savedMessage.value = '';
                        loadSavedChat();
                    }
                })
                .catch(() => {});
        }
        if (sendSavedBtn) {
            sendSavedBtn.addEventListener('click', sendSaved);
        }
        if (savedMessage) {
            savedMessage.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendSaved();
                }
            });
        }
        loadSavedChat();
    </script>
</body>
</html>