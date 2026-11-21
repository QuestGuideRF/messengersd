<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ./login/");
    exit;
}
include './config.php';
$query = new Database();
$sender_id = $_SESSION['user_id'];
$receiver_id = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($sender_id == $receiver_id || $receiver_id == null) {
    header("Location: ./");
    exit;
}
if (empty($query->select('users', '*', 'id = ?', [$receiver_id], 'i'))) {
    header("Location: ./");
    exit;
}
$sender_user = $query->select('users', '*', 'id = ?', [$sender_id], 'i')[0];
$receiver_user = $query->select('users', '*', 'id = ?', [$receiver_id], 'i')[0];
$blocked_sender = $query->select('block_users', '*', 'blocked_by = ? AND blocked_user = ?', [$receiver_id, $sender_id], 'ii');
$receiver_blocked = $query->select('block_users', '*', 'blocked_by = ? AND blocked_user = ?', [$sender_id, $receiver_id], 'ii');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Чат | <?= $receiver_user['full_name'] ?></title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link href="//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" rel="stylesheet" id="bootstrap-css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.5.0/css/all.css" integrity="sha384-B4dIYHKNBt8Bc12p+WXckhzcICo0wtJAoU8YZTY5qE0Id1GSseTk6S+L3BlXeVIU" crossorigin="anonymous">
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
                                <img src="./src/images/profile-picture/<?= $receiver_user['profile_picture'] ?>"
                                    class="rounded-circle user_img">
                            </div>
                            <div class="user_info">
                                <span><?= $receiver_user['full_name'] ?></span>
                                <p id="chat-subtitle"><b style="font-weight:normal"></b> </p>
                            </div>
                            <span id="action_menu_btn_user" style="padding: 5px; margin-left:auto;" onclick="createMenu(null, null)">
                                <i class="fas fa-ellipsis-v"></i>
                            </span>
                            <div class="action_menu_user" style="display: none;"></div>
                        </div>
                    </div>
                    <div class="modal fade" id="profileModal" tabindex="-1" role="dialog" aria-labelledby="profileModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document" style="display:flex; justify-content:center;">
                            <div class="modal-content" style="background: #7F7FD5; background: -webkit-linear-gradient(to right, #91EAE4, #86A8E7, #7F7FD5); background: linear-gradient(to right, #91EAE4, #86A8E7, #7F7FD5); border: none; border-radius: 11px; max-width:calc(100% - 20px); top: 15px">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="profileModalLabel">Профиль: <?= $receiver_user['full_name'] ?></h5>
                                    <button type="button" class="close" id="closeModalBtn" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center">
                                        <img src="./src/images/profile-picture/<?= $receiver_user['profile_picture'] ?>" class="rounded-circle mb-4" width="100" height="100">
                                        <h5><?= $receiver_user['full_name'] ?></h5>
                                        <p>@<?= $receiver_user['username'] ?></p>
                                        <p>Дата регистрации: <?= date("d.m.Y", strtotime($receiver_user['created_at'])) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body msg_card_body" id="messages-container">
                    </div>
                    <div class="blocked"></div>
                    <div class="card-footer">
                        <div class="input-group" id="send_msg">
                            <input type="file" id="attachment" class="d-none" />
                            <button type="button" class="input-group-text attach_btn" onclick="document.getElementById('attachment').click();" title="Прикрепить файл">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <textarea class="form-control type_msg" name="message" placeholder="Введите сообщение..."></textarea>
                            <div class="input-group-append">
                                <span class="input-group-text send_btn"><i class="fas fa-location-arrow"></i></span>
                            </div>
                        </div>
                        <div id="attachment-info" style="font-size: 12px; color: rgba(245,247,255,.8); margin-top:4px; display:none;">
                            Файл: <span id="attachment-name"></span>
                            <button type="button" id="attachment-clear" style="border:none;background:transparent;color:#e43c5a;font-weight:700;margin-left:6px;cursor:pointer;">
                                ✖
                            </button>
                        </div>
                    </div>
                    <script>
                        setInterval(function() {
                            const receiverId = <?= $receiver_id ?>;
                            fetch('./api/check_user_status.php?receiver_id=' + receiverId)
                                .then(response => response.json())
                                .then(data => {
                                    let blocked = document.querySelector('.blocked');
                                    if (data.status === 'blocked') {
                                        blocked.innerHTML = `
                                    <div class="blocked-message">
                                        <i class="fas fa-ban"></i>
                                        <p>Вы заблокированы!</p>
                                    </div>
                                    `;
                                    } else {
                                        blocked.innerHTML = '';
                                    }
                                })
                        }, 1000);
                    </script>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.0.9/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.all.min.js"></script>
    <script>
        function unlockStuckUiState() {
            const hasVisibleSwal = !!document.querySelector('.swal2-container.swal2-shown, .swal2-container.swal2-backdrop-show');
            const hasVisibleBootstrapModal = !!document.querySelector('.modal.show');
            if (hasVisibleSwal || hasVisibleBootstrapModal) {
                return;
            }
            document.body.style.pointerEvents = '';
            document.body.style.overflow = '';
            document.body.classList.remove('swal2-shown', 'swal2-height-auto', 'modal-open');
            document.querySelectorAll('.swal2-container, .swal2-backdrop, .modal-backdrop').forEach((el) => {
                if (!el.classList.contains('show') && !el.classList.contains('swal2-backdrop-show') && !el.classList.contains('swal2-toast-shown')) {
                    el.remove();
                }
            });
        }
        document.addEventListener("DOMContentLoaded", function() {
            unlockStuckUiState();
            window.addEventListener('focus', unlockStuckUiState);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    unlockStuckUiState();
                }
            });
            setInterval(unlockStuckUiState, 1500);
            const receiverId = <?= $receiver_id ?>;
            const senderId = <?= $sender_id ?>;
            const senderProfilePicture = "<?= $sender_user['profile_picture'] ?>";
            const receiverProfilePicture = "<?= $receiver_user['profile_picture'] ?>";
            let countScrollHeight = 0;
            const messagesContainer = document.getElementById('messages-container');
            let AllMessages = [];
            let replyToId = null;
            let replyPreview = null;
            const composer = document.querySelector('#send_msg');
            const messageInput = document.querySelector('.type_msg');
            const subtitle = document.getElementById('chat-subtitle');
            const attachInput = document.getElementById('attachment');
            const attachInfo = document.getElementById('attachment-info');
            const attachName = document.getElementById('attachment-name');
            const attachClear = document.getElementById('attachment-clear');
            if (attachInput && attachInfo && attachName && attachClear) {
                attachInput.addEventListener('change', function() {
                    const f = this.files && this.files[0];
                    if (f) {
                        attachName.textContent = f.name;
                        attachInfo.style.display = 'inline-block';
                    } else {
                        attachName.textContent = '';
                        attachInfo.style.display = 'none';
                    }
                });
                attachClear.addEventListener('click', function() {
                    attachInput.value = '';
                    attachName.textContent = '';
                    attachInfo.style.display = 'none';
                });
            }
            function formatLastSeen(ts) {
                if (!ts) return '';
                const d = new Date(String(ts).replace(' ', 'T'));
                if (isNaN(d.getTime())) return '';
                const hh = String(d.getHours()).padStart(2, '0');
                const mm = String(d.getMinutes()).padStart(2, '0');
                return `${hh}:${mm}`;
            }
            function setSubtitle(text) {
                if (subtitle) subtitle.textContent = text;
            }
            const receiverOnline = <?= !empty($receiver_user['is_online']) ? 'true' : 'false' ?>;
            const receiverLastSeen = <?= json_encode($receiver_user['last_seen'] ?? null, JSON_UNESCAPED_UNICODE) ?>;
            if (receiverOnline) {
                setSubtitle('в сети');
            } else {
                const t = formatLastSeen(receiverLastSeen);
                setSubtitle(t ? ('был(а) в сети в ' + t) : 'не в сети');
            }
            const replyBar = document.createElement('div');
            replyBar.className = 'reply-bar';
            replyBar.style.display = 'none';
            replyBar.innerHTML = `
                <div class="reply-bar__content">
                    <div class="reply-bar__title">Ответ</div>
                    <div class="reply-bar__text"></div>
                </div>
                <button type="button" class="reply-bar__close" aria-label="Отменить ответ">×</button>
            `;
            composer.parentNode.insertBefore(replyBar, composer);
            replyBar.querySelector('.reply-bar__close').addEventListener('click', () => {
                replyToId = null;
                replyPreview = null;
                replyBar.style.display = 'none';
            });
            function isNearBottom(el, threshold = 140) {
                if (!el) return true;
                const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
                return distance <= threshold;
            }
            function listSignature(list) {
                if (!Array.isArray(list) || list.length === 0) return 'empty';
                const last = list[list.length - 1];
                return [
                    list.length,
                    last.id,
                    last.created_at,
                    last.status,
                    last.is_edited,
                    last.deleted_for_sender,
                    last.deleted_for_receiver
                ].join('|');
            }
            let lastRenderedSignature = '';
            window.LoadMessages = function LoadMessages() {
                $.ajax({
                    url: './api/fetch_messages.php',
                    type: 'POST',
                    data: {
                        id: receiverId
                    },
                    dataType: 'json',
                    success: function(response) {
                        AllMessages = (response && response.data) ? response.data : [];
                        window.AllMessages = AllMessages;
                        const searchActive = !!((searchBox.value || '').trim());
                        const sig = listSignature(AllMessages);
                        const wasAtBottom = isNearBottom(messagesContainer);
                        const prevScrollHeight = messagesContainer.scrollHeight;
                        const prevScrollTop = messagesContainer.scrollTop;
                        if (!searchActive && sig === lastRenderedSignature) {
                            return;
                        }
                        if (AllMessages && AllMessages.length > 0) {
                            let countElement = document.querySelector('.user_info p b');
                            if (countElement) {
                                countElement.textContent = AllMessages.length;
                            }
                            renderMessages(AllMessages);
                            if (countScrollHeight == 0) {
                                messagesContainer.scrollTop = messagesContainer.scrollHeight;
                                countScrollHeight++;
                            }
                        } else {
                            messagesContainer.innerHTML = `
                            <div class="no-messages-container">
                                <i class="fas fa-comment-slash fa-3x"></i>
                                <p class="no-messages">Сообщений пока нет.</p>
                            </div>`;
                            let countElement = document.querySelector('.user_info p b');
                            if (countElement) {
                                countElement.textContent = AllMessages.length;
                            }
                        }
                        if (!wasAtBottom) {
                            const newScrollHeight = messagesContainer.scrollHeight;
                            messagesContainer.scrollTop = prevScrollTop + (newScrollHeight - prevScrollHeight);
                        } else {
                            messagesContainer.scrollTop = messagesContainer.scrollHeight;
                        }
                        lastRenderedSignature = sig;
                    }
                });
            }
            window.LoadMessages();
            setInterval(window.LoadMessages, 1000);
            const searchBox = document.createElement('input');
            searchBox.type = 'text';
            searchBox.className = 'form-control search mt-2';
            searchBox.placeholder = 'Поиск по сообщениям...';
            messagesContainer.parentNode.insertBefore(searchBox, messagesContainer);
            searchBox.addEventListener('input', function() {
                const term = (this.value || '').toLowerCase();
                if (!term) {
                    renderMessages(AllMessages);
                    return;
                }
                const filtered = (AllMessages || []).filter(m => (m.content || '').toLowerCase().includes(term));
                renderMessages(filtered);
            });
            fetch('./api/get_draft.php?peer_type=user&peer_id=' + encodeURIComponent(receiverId))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success' && res.data && typeof res.data.content === 'string') {
                        messageInput.value = res.data.content;
                    }
                })
                .catch(() => {});
            let draftTimer = null;
            messageInput.addEventListener('input', () => {
                if (window.__typingTimer) clearTimeout(window.__typingTimer);
                window.__typingTimer = setTimeout(() => {
                    const fdTyping = new FormData();
                    fdTyping.append('peer_type', 'user');
                    fdTyping.append('peer_id', receiverId);
                    fetch('./api/set_typing.php', { method: 'POST', body: fdTyping }).catch(() => {});
                }, 250);
                if (draftTimer) clearTimeout(draftTimer);
                draftTimer = setTimeout(() => {
                    const fd = new FormData();
                    fd.append('peer_type', 'user');
                    fd.append('peer_id', receiverId);
                    fd.append('content', messageInput.value);
                    fetch('./api/save_draft.php', { method: 'POST', body: fd }).catch(() => {});
                }, 400);
            });
            setInterval(() => {
                fetch('./api/check_typing.php?peer_type=user&peer_id=' + encodeURIComponent(receiverId))
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'success' && res.data) {
                            if (res.data.is_typing) {
                                setSubtitle('печатает…');
                            } else {
                                if (receiverOnline) {
                                    setSubtitle('в сети');
                                } else {
                                    const t = formatLastSeen(receiverLastSeen);
                                    setSubtitle(t ? ('был(а) в сети в ' + t) : 'не в сети');
                                }
                            }
                        }
                    })
                    .catch(() => {});
            }, 2000);
        });
        function formatTime(ts) {
            if (!ts) return '';
            const d = new Date(ts.replace(' ', 'T'));
            if (isNaN(d.getTime())) return ts;
            const hh = String(d.getHours()).padStart(2, '0');
            const mm = String(d.getMinutes()).padStart(2, '0');
            return `${hh}:${mm}`;
        }
        function formatDateLabel(ts) {
            const d = new Date(ts.replace(' ', 'T'));
            if (isNaN(d.getTime())) return ts;
            const months = ['января','февраля','марта','апреля','мая','июня','июля','августа','сентября','октября','ноября','декабря'];
            return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
        }
        function renderMessages(list) {
            const messagesContainer = document.getElementById('messages-container');
            messagesContainer.innerHTML = '';
            if (!list || list.length === 0) {
                messagesContainer.innerHTML = `
                    <div class="no-messages-container">
                        <i class="fas fa-comment-slash fa-3x"></i>
                        <p class="no-messages">Сообщений пока нет.</p>
                    </div>`;
                let countElement = document.querySelector('.user_info p b');
                if (countElement) {
                    countElement.textContent = 0;
                }
                return;
            }
            const senderId = <?= $sender_id ?>;
            const senderProfilePicture = "<?= $sender_user['profile_picture'] ?>";
            const receiverProfilePicture = "<?= $receiver_user['profile_picture'] ?>";
            let lastDate = '';
            list.forEach(Message => {
                const datePart = (Message.created_at || '').split(' ')[0] || '';
                if (datePart && datePart !== lastDate) {
                    lastDate = datePart;
                    const label = formatDateLabel(Message.created_at);
                    messagesContainer.innerHTML += `
                        <div class="day-separator"><span>${label}</span></div>
                    `;
                }
                const isSender = Message.sender_id === senderId;
                const timeLabel = formatTime(Message.created_at);
                const isEdited = Message.is_edited == 1;
                const isDeletedForSender = Message.deleted_for_sender == 1;
                const isDeletedForReceiver = Message.deleted_for_receiver == 1;
                const isAdmin = <?= isset($sender_user['is_admin']) && $sender_user['is_admin'] ? 'true' : 'false' ?>;
                let content = Message.content;
                let deletedForMe = false;
                if (!isAdmin) {
                    if (isSender && isDeletedForSender) deletedForMe = true;
                    if (!isSender && isDeletedForReceiver) deletedForMe = true;
                }
                let contentHtml;
                let extraClass = '';
                if (deletedForMe) {
                    contentHtml = '<span class="msg-deleted">Сообщение удалено</span>';
                } else {
                    const safe = $('<div/>').text(content).html();
                    if (isAdmin && (isDeletedForSender || isDeletedForReceiver)) {
                        extraClass = ' msg-admin-deleted';
                    }
                    contentHtml = safe;
                }
                const editedMark = isEdited ? '<span class="msg-edited" title="Изменено">✏️</span>' : '';
                let statusMarkup = '';
                if (isSender) {
                    if (Message.status === 'read') {
                        statusMarkup = '<span class="msg-status msg-status-read" title="Прочитано">✔✔</span>';
                    } else {
                        statusMarkup = '<span class="msg-status msg-status-sent" title="Отправлено">✔</span>';
                    }
                }
                let replyHtml = '';
                if (Message.reply_to_id && Message.reply_preview) {
                    const safePreview = $('<div/>').text(Message.reply_preview).html();
                    replyHtml = `
                        <div class="reply-preview" data-reply-to="${Message.reply_to_id}">
                            <div class="reply-preview__label">Ответ</div>
                            <div class="reply-preview__text">${safePreview}</div>
                        </div>
                    `;
                }
                let reactionsHtml = '';
                if (Message.reactions && Message.reactions.length) {
                    const items = Message.reactions.map(r => {
                        const mine = r.mine ? ' reaction--mine' : '';
                        return `<button type="button" class="reaction${mine}" onclick="react(${Message.id}, '${r.emoji.replace(/'/g, "\\'")}')">${r.emoji} <span>${r.count}</span></button>`;
                    }).join('');
                    reactionsHtml = `<div class="reactions">${items}</div>`;
                }
                let attachmentsHtml = '';
                if (Message.attachments && Message.attachments.length) {
                    const parts = Message.attachments.map(a => {
                        if (a.is_image && a.thumb_path) {
                            return `
                                <a class="attachment attachment--image" href="./download.php?id=${a.id}" target="_blank" rel="noopener">
                                    <img src="${a.thumb_path}" alt="${(a.original_name || '').replace(/"/g,'&quot;')}" onerror="this.style.display='none'">
                                </a>
                            `;
                        }
                        if (a.is_image) {
                            return `
                                <a class="attachment attachment--image" href="./download.php?id=${a.id}" target="_blank" rel="noopener">
                                    <span>🖼️ ${a.original_name}</span>
                                </a>
                            `;
                        }
                        return `
                            <a class="attachment" href="./download.php?id=${a.id}">
                                <span>📎 ${a.original_name}</span>
                            </a>
                        `;
                    }).join('');
                    attachmentsHtml = `<div class="attachments">${parts}</div>`;
                }
                const showTextBlock = !(String(contentHtml || '').trim() === '' && attachmentsHtml);
                if (isSender) {
                    messagesContainer.innerHTML += `
                        <div class="d-flex justify-content-end mb-4 message-container" style="margin-left:15px" data-message-id="${Message.id}" id="sender">
                            <div style="display: flex; justify-content: center; align-items:center">
                                <div class="relative-container" id="sender">
                                    <span class="action_menu_btn" style="cursor: pointer; padding: 5px"><i class="fas fa-ellipsis-v" style="color: #78e08f;"></i></span>
                                </div>
                                <div class="msg_cotainer_send${extraClass}">
                                    ${replyHtml}
                                    ${showTextBlock ? `<div class="message-text">${contentHtml}</div>` : ''}
                                    ${attachmentsHtml}
                                    ${reactionsHtml}
                                    <span class="msg_time_send">${timeLabel} ${editedMark} ${statusMarkup}</span>
                                </div>
                            </div>
                            <div class="img_cont_msg">
                                <img src="./src/images/profile-picture/${senderProfilePicture}" class="rounded-circle user_img_msg">
                            </div>
                        </div>
                    `;
                } else {
                    messagesContainer.innerHTML += `
                        <div class="d-flex justify-content-start mb-4 message-container" style="margin-right:15px" data-message-id="${Message.id}" id="receiver">
                            <div class="img_cont_msg">
                                <img src="./src/images/profile-picture/${receiverProfilePicture}" class="rounded-circle user_img_msg">
                            </div>
                            <div style="display: flex; justify-content: center; align-items:center">
                                <div class="msg_cotainer${extraClass}">
                                    ${replyHtml}
                                    ${showTextBlock ? `<div class="message-text">${contentHtml}</div>` : ''}
                                    ${attachmentsHtml}
                                    ${reactionsHtml}
                                    <span class="msg_time">${timeLabel} ${editedMark}</span>
                                </div>
                                <div class="relative-container" id="receiver">
                                    <span class="action_menu_btn" style="cursor: pointer; padding: 5px"><i class="fas fa-ellipsis-v" style="color: #b8daff;"></i></span>
                                </div>
                            </div>
                        </div>
                    `;
                }
            });
            let countElement = document.querySelector('.user_info p b');
            if (countElement) {
                countElement.textContent = list.length;
            }
        }
        function createMenu(id, user) {
            const action_menu_user = document.querySelector('.action_menu_user');
            if (id == null && user == null) {
                action_menu_user.style = `top: 22px; right: 22px;`;
                action_menu_user.innerHTML = `<ul>
            <li><i class="fas fa-user-circle"></i> Профиль</li>
            <li style="color: white" onclick="pinChat()"><i class="fas fa-thumbtack"></i> Закрепить</li>
            <li style="color: white" onclick="unpinChat()"><i class="fas fa-thumbtack"></i> Открепить</li>
            <li style="color: white" onclick="archiveChat()"><i class="fas fa-archive"></i> В архив</li>
            <li style="color: white" onclick="unarchiveChat()"><i class="fas fa-inbox"></i> Из архива</li>
            <li style="color: orange" onclick="clearMessages()"><i class="fas fa-times-circle"></i> Очистить</li>
            <?php if (empty($receiver_blocked)) : ?>
                <li style="color: red" onclick="block(<?= $receiver_id ?>)">
                    <i class="fas fa-ban"></i> Заблокировать
                </li>
            <?php else : ?>
                <li style="color: green" onclick="unBlock(<?= $receiver_id ?>)">
                    <i class="fas fa-check-circle"></i> Разблокировать
                </li>
            <?php endif; ?>
            </ul>`;
            } else if (user == 'sender') {
                action_menu_user.style = `top: 90px; right: 90px;`;
                action_menu_user.innerHTML = `<ul>
                <li style="color: white" onclick="replyTo(${id})"><i class="fas fa-reply"></i> Ответить</li>
                <li style="color: white" onclick="openReactions(${id})"><i class="far fa-smile"></i> Реакция</li>
                <li style="color: white" onclick="toggleSaved(${id})"><i class="fas fa-bookmark"></i> Сохранить</li>
                <li class="copy-option" style="color: white" onclick="copyMessage(${id})"><i class="fas fa-copy"></i> Копировать</li>
                <li class="edit-option" style="color: orange" onclick="edit(${id})"><i class="fas fa-edit"></i> Редактировать</li>
                <li class="delete-option" style="color: red" onclick="deleteMessage(${id})"><i class="fas fa-trash-alt"></i> Удалить</li>
            </ul>`;
            } else {
                action_menu_user.style = `top: 90px; left: 90px;`;
                action_menu_user.innerHTML = `<ul>
                <li style="color: white" onclick="replyTo(${id})"><i class="fas fa-reply"></i> Ответить</li>
                <li style="color: white" onclick="openReactions(${id})"><i class="far fa-smile"></i> Реакция</li>
                <li style="color: white" onclick="toggleSaved(${id})"><i class="fas fa-bookmark"></i> Сохранить</li>
                <li class="copy-option" style="color: white" onclick="copyMessage(${id})"><i class="fas fa-copy"></i> Копировать</li>
                <li class="delete-option" style="color: red" onclick="deleteMessage(${id})"><i class="fas fa-trash-alt"></i> Удалить</li>
            </ul>`;
            }
        }
        function copyMessage(id) {
            const senderMessageElement = document.querySelector(`[data-message-id="${id}"] .msg_cotainer_send div`);
            const receiverMessageElement = document.querySelector(`[data-message-id="${id}"] .msg_cotainer div`);
            const messageElement = senderMessageElement || receiverMessageElement;
            if (messageElement) {
                const messageText = messageElement.innerText;
                navigator.clipboard.writeText(messageText).then(() => {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Сообщение скопировано',
                        showConfirmButton: false,
                        timer: 1500,
                        toast: true,
                        background: '#4CAF50',
                        color: '#fff'
                    });
                })
            }
        }
        function block(userId) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', 'block');
            fetch('./api/change_user_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Готово',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1000
                        }).then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            text: data.message,
                            showConfirmButton: true
                        });
                    }
                })
        }
        function unBlock(userId) {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('action', 'unblock');
            fetch('./api/change_user_status.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        Swal.fire({
                                icon: 'success',
                                title: 'Готово',
                                text: data.message,
                                showConfirmButton: false,
                                timer: 1000
                            })
                            .then(() => window.location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Ошибка',
                            text: data.message,
                            showConfirmButton: true
                        });
                    }
                });
        }
    </script>
    <script>
        let isOpen = null;
        function toggleActionMenu(event, actionMenuSelector) {
            event.stopPropagation();
            const actionMenu = document.querySelector(actionMenuSelector);
            if (isOpen && isOpen !== actionMenu) {
                isOpen.style.display = 'none';
            }
            if (actionMenu.style.display === 'block') {
                actionMenu.style.display = 'none';
                isOpen = null;
            } else {
                actionMenu.style.display = 'block';
                isOpen = actionMenu;
            }
        }
        document.getElementById('action_menu_btn_user').addEventListener('click', function(event) {
            toggleActionMenu(event, '.action_menu_user');
        });
        document.querySelector('.msg_card_body').addEventListener('click', function(event) {
            if (event.target.closest('.action_menu_btn')) {
                const messageContainer = event.target.closest('.message-container');
                const messageId = messageContainer ? messageContainer.getAttribute('data-message-id') : null;
                createMenu(messageId, messageContainer.id);
                toggleActionMenu(event, '.action_menu_user');
            }
        });
        document.querySelector('.action_menu_user').addEventListener('click', function(event) {
            const clickedItem = event.target.closest('li');
            if (clickedItem && clickedItem.textContent.trim() === 'Профиль') {
                unlockStuckUiState();
                $('#profileModal').modal('show');
            }
        });
        document.addEventListener('click', function(event) {
            if (isOpen && !isOpen.contains(event.target) && !event.target.closest('.action_menu_btn') && !event.target.closest('#action_menu_btn_user')) {
                isOpen.style.display = 'none';
                isOpen = null;
            }
        });
        $('#profileModal').on('hidden.bs.modal', unlockStuckUiState);
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                setTimeout(unlockStuckUiState, 50);
            }
        });
    </script>
    <script>
        document.querySelector('.send_btn').addEventListener('click', function(event) {
            event.preventDefault();
            const receiverId = <?= $receiver_id ?>;
            const block_by = "<?= $receiver_user['full_name'] ?>";
            async function userBlocked() {
                const response = await fetch('./api/check_user_status.php?receiver_id=' + receiverId);
                const data = await response.json();
                if (data.status === 'blocked') {
                    return true;
                }
                return false;
            }
            userBlocked().then(isBlocked => {
                if (isBlocked) {
                    Swal.fire({
                        title: `Вы заблокированы`,
                        text: `Пользователь «${block_by}» заблокировал вас. Вы не можете отправлять сообщения.`,
                        icon: 'error',
                        confirmButtonText: 'Понятно',
                        confirmButtonColor: '#d33',
                        background: '#fff3f3',
                        customClass: {
                            title: 'swal-title',
                            content: 'swal-content',
                        }
                    });
                } else {
                    const messageInput = document.querySelector('.type_msg');
                    const message = messageInput.value.trim();
                    const receiver_id = <?= $receiver_id ?>;
                    const fileEl = document.getElementById('attachment');
                    const hasFile = fileEl && fileEl.files && fileEl.files[0];
                    if (!message && !hasFile) return;
                    const fd = new FormData();
                    fd.append('content', message);
                    fd.append('receiver_id', receiver_id);
                    if (window.__replyToId) fd.append('reply_to_id', window.__replyToId);
                    if (hasFile) {
                        fd.append('attachment', fileEl.files[0]);
                    }
                    $.ajax({
                        url: './api/send_message.php',
                        method: 'POST',
                        data: fd,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.status === 'success') {
                                messageInput.value = '';
                                if (fileEl) fileEl.value = '';
                                const attachInfo = document.getElementById('attachment-info');
                                const attachName = document.getElementById('attachment-name');
                                if (attachInfo && attachName) {
                                    attachName.textContent = '';
                                    attachInfo.style.display = 'none';
                                }
                                const fd = new FormData();
                                fd.append('peer_type', 'user');
                                fd.append('peer_id', receiver_id);
                                fd.append('content', '');
                                fetch('./api/save_draft.php', { method: 'POST', body: fd }).catch(() => {});
                                window.__replyToId = null;
                                const rb = document.querySelector('.reply-bar');
                                if (rb) rb.style.display = 'none';
                                LoadMessages();
                            }
                        }
                    });
                }
            });
        });
        function edit(messageId) {
            const messageContainer = document.querySelector(`.message-container[data-message-id="${messageId}"]`);
            if (messageContainer) {
                const messageElement = messageContainer.querySelector('.msg_cotainer_send div');
                if (messageElement) {
                    const messageText = messageElement.textContent.trim();
                    Swal.fire({
                        title: 'Редактировать сообщение',
                        input: 'textarea',
                        inputValue: messageText,
                        inputPlaceholder: 'Введите текст...',
                        showCancelButton: true,
                        confirmButtonText: 'Сохранить',
                        cancelButtonText: 'Отмена',
                        inputAttributes: {
                            'aria-label': 'Введите сообщение'
                        },
                        inputValidator: (value) => {
                            if (!value) {
                                return 'Введите текст сообщения';
                            }
                        },
                        customClass: {
                            input: 'swal2-textarea'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const newMessage = result.value;
                            $.ajax({
                                url: './api/edit_message.php',
                                method: 'POST',
                                data: {
                                    message_id: messageId,
                                    new_message: newMessage
                                },
                                success: function(response) {
                                    if (response.status === 'success') {
                                        messageElement.textContent = newMessage;
                                        Swal.fire({
                                            title: 'Готово!',
                                            text: response.message,
                                            icon: 'success',
                                            showConfirmButton: false,
                                            timer: 1000
                                        });
                                    }
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire('Ошибка', 'Не удалось найти текст сообщения. Попробуйте ещё раз.', 'error');
                }
            } else {
                Swal.fire('Ошибка', 'Не удалось найти сообщение. Попробуйте ещё раз.', 'error');
            }
        }
        function deleteMessage(messageId) {
            Swal.fire({
                title: 'Удалить сообщение?',
                text: 'Выберите вариант удаления.',
                icon: 'warning',
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonColor: '#d33',
                denyButtonColor: '#3085d6',
                confirmButtonText: 'Удалить у всех',
                denyButtonText: 'Удалить только у меня',
                cancelButtonText: 'Отмена'
            }).then((result) => {
                if (!result.isConfirmed && !result.isDenied) {
                    return;
                }
                const scope = result.isConfirmed ? 'all' : 'me';
                $.ajax({
                    url: './api/delete_message.php',
                    method: 'POST',
                    data: {
                        message_id: messageId,
                        scope: scope
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Удалено',
                                text: response.message,
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1000
                            });
                            LoadMessages();
                        } else {
                            Swal.fire('Ошибка', response.message, 'error');
                        }
                    }
                });
            });
        }
        function clearMessages() {
            Swal.fire({
                title: 'Вы уверены?',
                text: 'Очистить всю переписку?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Да, очистить',
                cancelButtonText: 'Отмена'
            }).then((result) => {
                if (result.isConfirmed) {
                    const receiverId = <?= $receiver_id ?>;
                    $.ajax({
                        url: './api/clear_messages.php',
                        method: 'POST',
                        data: {
                            clear: true,
                            receiver_id: receiverId
                        },
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    title: 'Готово!',
                                    text: response.message,
                                    icon: 'success',
                                    showConfirmButton: false,
                                    timer: 1000
                                });
                            }
                        }
                    });
                }
            });
        }
    </script>
    <script>
        window.__replyToId = null;
        function replyTo(messageId) {
            const m = (window.AllMessages || []).find(x => String(x.id) === String(messageId));
            const text = m ? (m.content || '') : '';
            window.__replyToId = messageId;
            const rb = document.querySelector('.reply-bar');
            if (rb) {
                rb.querySelector('.reply-bar__text').textContent = text.length > 80 ? (text.slice(0, 80) + '…') : text;
                rb.style.display = 'flex';
            }
            document.querySelector('.type_msg')?.focus();
        }
        function openReactions(messageId) {
            Swal.fire({
                title: 'Реакция',
                input: 'select',
                inputOptions: {
                    '👍': '👍',
                    '❤️': '❤️',
                    '😂': '😂',
                    '🔥': '🔥',
                    '😮': '😮'
                },
                inputPlaceholder: 'Выберите',
                showCancelButton: true,
                confirmButtonText: 'Поставить',
                cancelButtonText: 'Отмена'
            }).then((res) => {
                if (res.isConfirmed && res.value) {
                    react(messageId, res.value);
                }
            });
        }
        function react(messageId, emoji) {
            const fd = new FormData();
            fd.append('message_id', messageId);
            fd.append('emoji', emoji);
            fetch('./api/toggle_reaction.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(() => {
                    if (typeof LoadMessages === 'function') {
                        LoadMessages();
                    }
                })
                .catch(() => {});
        }
        function toggleSaved(messageId) {
            const fd = new FormData();
            fd.append('message_id', messageId);
            fetch('./api/toggle_saved.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 900, showConfirmButton: false });
                })
                .catch(() => {});
        }
    </script>
    <script>
        function postChatAction(url, action) {
            const fd = new FormData();
            fd.append('peer_type', 'user');
            fd.append('peer_id', <?= (int)$receiver_id ?>);
            fd.append('action', action);
            return fetch(url, { method: 'POST', body: fd }).then(r => r.json());
        }
        function pinChat() {
            postChatAction('./api/chat_pin.php', 'pin').then(res => {
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 900, showConfirmButton: false });
            });
        }
        function unpinChat() {
            postChatAction('./api/chat_pin.php', 'unpin').then(res => {
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 900, showConfirmButton: false });
            });
        }
        function archiveChat() {
            postChatAction('./api/chat_archive.php', 'archive').then(res => {
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 900, showConfirmButton: false })
                    .then(() => window.location.href = './');
            });
        }
        function unarchiveChat() {
            postChatAction('./api/chat_archive.php', 'unarchive').then(res => {
                Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 900, showConfirmButton: false });
            });
        }
    </script>
</body>
</html>