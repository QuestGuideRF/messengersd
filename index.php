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
    <title>Мессенджер | Контакты</title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./src/css/style.css">
    <link rel="stylesheet" href="./src/css/profile-modal.css">
</head>
<body>
    <div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="profileModalLabel">Профиль</h5>
                    <button type="button" class="close btn-close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true" style="font-size:2rem;">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="profile-img-preview">
                        <img src="./src/images/profile-picture/default.png" id="modalProfilePicture" alt="Profile Preview">
                    </div>
                    <form id="profile-form" method="POST" enctype="multipart/form-data" autocomplete="off">
                        <div class="form-group mb-3">
                            <label for="full_name" class="form-label">Имя:</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required maxlength="30" placeholder="Введите имя">
                        </div>
                        <div class="form-group mb-3">
                            <label for="profile_picture" class="form-label">Фото профиля:</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="profile_picture" name="profile_picture" accept="image/*">
                                <label class="custom-file-label" for="profile_picture">Выберите изображение</label>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <label for="username" class="form-label">Никнейм:</label>
                            <input type="text" id="username" name="username" class="form-control" readonly>
                        </div>
                        <div class="form-group mb-3">
                            <label for="theme" class="form-label">Тема:</label>
                            <select id="theme" name="theme" class="form-control">
                                <option value="dark">Тёмная</option>
                                <option value="light">Светлая</option>
                                <option value="system">Как в системе</option>
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sound_enabled" name="sound_enabled" checked>
                                <label class="custom-control-label" for="sound_enabled">Звук уведомлений</label>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Сохранить</button>
                    </form>
                    <hr style="border-color: rgba(255,255,255,.10); margin:16px 0;">
                    <div>
                        <h6 style="margin-bottom:10px; font-weight:900;">Активные сессии</h6>
                        <div id="sessionsList" style="opacity:.9;"></div>
                        <button class="btn btn-outline-light btn-sm" id="logoutOthers" style="border-radius:12px;">Разлогинить все кроме текущей</button>
                    </div>
                    <hr style="border-color: rgba(255,255,255,.10); margin:16px 0;">
                    <form id="pin-form" autocomplete="off">
                        <div class="form-group mb-3">
                            <label for="current_pin" class="form-label">Текущий PIN</label>
                            <input type="password" id="current_pin" name="current_pin" class="form-control" inputmode="numeric" placeholder="••••">
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_pin" class="form-label">Новый PIN (4 цифры)</label>
                            <input type="password" id="new_pin" name="new_pin" class="form-control" inputmode="numeric" placeholder="••••">
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_pin_confirm" class="form-label">Повторите новый PIN</label>
                            <input type="password" id="new_pin_confirm" name="new_pin_confirm" class="form-control" inputmode="numeric" placeholder="••••">
                        </div>
                        <div class="invalid-feedback" id="pin-error" style="display:block;"></div>
                        <button type="submit" class="btn btn-primary btn-block">Сменить PIN</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid h-100">
        <div class="row justify-content-center h-100">
            <div class="col-md-8 col-xl-6 chat">
                <div class="card">
                    <div class="card-header d-flex">
                        <div class="input-group">
                            <button type="button" class="input-group-text menu_btn" id="openProfileModal" title="Профиль">
                                <i class="fas fa-bars"></i>
                            </button>
                            <input type="text" placeholder="Поиск..." name="search" id="search" class="form-control search">
                        </div>
                    </div>
                    <div class="card-body contacts_body">
                        <ul class="contacts" id="contacts-list"></ul>
                        <div id="contacts-empty" class="text-center text-muted mt-5" style="display:none;">Контакты не найдены.</div>
                    </div>
                    <div class="card-footer"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js"></script>
    <script>
        const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
        let groupsCache = [];
        let lastGroupsSignature = '';
        function truncatePreviewByWords(text, maxLength = 42) {
            const normalized = String(text || '').replace(/\s+/g, ' ').trim();
            if (!normalized || normalized.length <= maxLength) {
                return normalized;
            }
            const slice = normalized.slice(0, maxLength + 1);
            const lastSpace = slice.lastIndexOf(' ');
            const cutoff = lastSpace >= Math.floor(maxLength * 0.6) ? lastSpace : maxLength;
            return normalized.slice(0, cutoff).trim().replace(/[.,!?:;\-]+$/u, '') + '...';
        }
        function groupsSignature(list) {
            if (!Array.isArray(list) || list.length === 0) return 'empty';
            return list.map(g => [g.id, g.title, g.members_count].join(':')).join('|');
        }
        function fetchGroupsCache() {
            fetch('./api/groups_list.php')
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') return;
                    const sig = groupsSignature(res.data || []);
                    if (sig !== lastGroupsSignature) {
                        lastGroupsSignature = sig;
                        groupsCache = res.data || [];
                        lastContactsSignature = '';
                    }
                })
                .catch(() => {});
        }
        function fetchUserProfile() {
            fetch('./api/fetch_profile.php')
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        const {
                            full_name,
                            username,
                            profile_picture
                            , theme
                            , sound_enabled
                        } = result.data;
                        document.getElementById('full_name').value = full_name;
                        document.getElementById('username').value = username;
                        const profilePic = profile_picture && profile_picture !== 'default.png' ?
                            './src/images/profile-picture/' + profile_picture :
                            './src/images/profile-picture/default.png';
                        document.getElementById('modalProfilePicture').src = profilePic;
                        if (theme) document.getElementById('theme').value = theme;
                        document.getElementById('sound_enabled').checked = (Number(sound_enabled) || 0) ? true : false;
                        applyTheme(theme || 'dark');
                    }
                });
        }
        function applyTheme(theme) {
            document.body.classList.remove('theme-light');
            if (theme === 'light') {
                document.body.classList.add('theme-light');
                return;
            }
            if (theme === 'system') {
                const prefersLight = window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches;
                if (prefersLight) document.body.classList.add('theme-light');
            }
        }
        function loadSessions() {
            fetch('./api/sessions.php')
                .then(r => r.json())
                .then(res => {
                    const el = document.getElementById('sessionsList');
                    if (!el) return;
                    if (res.status !== 'success') {
                        el.innerHTML = '<div style="opacity:.7;">Не удалось загрузить сессии.</div>';
                        return;
                    }
                    const rows = res.data || [];
                    if (!rows.length) {
                        el.innerHTML = '<div style="opacity:.7;">Нет активных сессий.</div>';
                        return;
                    }
                    el.innerHTML = rows.map(s => {
                        const current = s.is_current ? ' (текущая)' : '';
                        const active = Number(s.is_active) ? '' : ' • отключена';
                        return `<div style="padding:6px 0;border-bottom:1px solid rgba(255,255,255,.08);">
                            <div style="font-weight:800;">${(s.ip || 'IP неизвестен')}${current}${active}</div>
                            <div style="opacity:.7;font-size:12px;word-break:break-word;">${(s.user_agent || 'UA неизвестен')}</div>
                        </div>`;
                    }).join('');
                })
                .catch(() => {});
        }
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(ev) {
                    document.getElementById('modalProfilePicture').src = ev.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.set('sound_enabled', document.getElementById('sound_enabled').checked ? '1' : '0');
            fetch('./api/fetch_profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(result => {
                    Swal.fire({
                        icon: 'success',
                        title: 'Профиль обновлён',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => window.location.reload());
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ошибка сервера',
                        text: 'Не удалось подключиться к серверу. Попробуйте позже.',
                    });
                });
        });
        function onlyDigits(el, maxLen) {
            el.addEventListener('input', () => {
                el.value = (el.value || '').replace(/\D+/g, '').slice(0, maxLen);
            });
        }
        onlyDigits(document.getElementById('current_pin'), 4);
        onlyDigits(document.getElementById('new_pin'), 4);
        onlyDigits(document.getElementById('new_pin_confirm'), 4);
        document.getElementById('pin-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const err = document.getElementById('pin-error');
            err.textContent = '';
            const currentPin = (document.getElementById('current_pin').value || '').trim();
            const newPin = (document.getElementById('new_pin').value || '').trim();
            const newPinConfirm = (document.getElementById('new_pin_confirm').value || '').trim();
            if (!currentPin || !newPin || !newPinConfirm) {
                err.textContent = 'Заполните все поля PIN';
                return;
            }
            if (!/^\d{4}$/.test(newPin)) {
                err.textContent = 'Новый PIN должен состоять из 4 цифр';
                return;
            }
            if (newPin !== newPinConfirm) {
                err.textContent = 'PIN-коды не совпадают';
                return;
            }
            const formData = new FormData();
            formData.append('current_pin', currentPin);
            formData.append('new_pin', newPin);
            formData.append('new_pin_confirm', newPinConfirm);
            fetch('./api/auth/change_pin.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(result => {
                    if (result.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'PIN изменён',
                            text: result.message,
                            showConfirmButton: false,
                            timer: 1400
                        }).then(() => {
                            document.getElementById('current_pin').value = '';
                            document.getElementById('new_pin').value = '';
                            document.getElementById('new_pin_confirm').value = '';
                        });
                    } else {
                        err.textContent = result.message || 'Не удалось изменить PIN';
                    }
                })
                .catch(() => {
                    err.textContent = 'Не удалось подключиться к серверу. Попробуйте позже.';
                });
        });
        fetchUserProfile();
        loadSessions();
        fetchGroupsCache();
        setInterval(fetchGroupsCache, 5000);
        document.getElementById('logoutOthers').addEventListener('click', function() {
            const fd = new FormData();
            fd.append('action', 'logout_others');
            fetch('./api/sessions.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(res => Swal.fire({ icon: res.status === 'success' ? 'success' : 'error', title: res.message || 'Готово', timer: 1200, showConfirmButton: false }).then(loadSessions));
        });
        try {
            const openBtn = document.getElementById('openProfileModal');
            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    try { $('#profileModal').attr('aria-hidden', 'false'); } catch {}
                    $('#profileModal').modal('show');
                });
            }
            $('#profileModal').on('shown.bs.modal', function() {
                try { $(this).attr('aria-hidden', 'false'); } catch {}
            });
            $('#profileModal').on('hide.bs.modal', function() {
                try {
                    if (document.activeElement && this.contains(document.activeElement)) {
                        document.activeElement.blur();
                    }
                } catch {}
            });
            $('#profileModal').on('hidden.bs.modal', function() {
                try { $(this).attr('aria-hidden', 'true'); } catch {}
                try { if (openBtn) openBtn.focus(); } catch {}
            });
        } catch {}
        let fetchInterval = setInterval(fetchContacts, 1000);
        const searchInput = document.getElementById('search');
        let timeout = null;
        searchInput.addEventListener('input', function() {
            clearInterval(fetchInterval);
            if (timeout) clearTimeout(timeout);
            timeout = setTimeout(function() {
                fetchContacts(searchInput.value.trim());
            }, 500);
        });
        searchInput.addEventListener('blur', function() {
            fetchInterval = setInterval(fetchContacts, 1000);
        });
        let lastContactsSignature = '';
        function contactsSignature(list) {
            if (!Array.isArray(list) || list.length === 0) return 'empty';
            return list.map(u => [u.user_id, u.unread_messages, u.pinned_at || '', u.last_message_time || ''].join(':')).join('|');
        }
        function fetchContacts(searchTerm = '') {
            fetch('./api/fetch_contacts.php?search=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    const contactsList = document.getElementById('contacts-list');
                    const contactsEmpty = document.getElementById('contacts-empty');
                    if (data.status === 'success' && data.data.length > 0) {
                        contactsEmpty.style.display = 'none';
                        const sig = contactsSignature(data.data);
                        if (sig === lastContactsSignature && !searchTerm) {
                            return;
                        }
                        lastContactsSignature = sig;
                        const scrollBox = document.querySelector('.contacts_body');
                        const prevTop = scrollBox ? scrollBox.scrollTop : 0;
                        contactsList.innerHTML = '';
                        const favItem = document.createElement('li');
                        favItem.setAttribute('onclick', `window.location.href='saved_chat.php'`);
                        favItem.innerHTML = `
                            <div class="d-flex bd-highlight">
                                <div class="img_cont">
                                    <div class="rounded-circle user_img" style="display:flex;align-items:center;justify-content:center;background:rgba(110,231,255,.18);border:1px solid rgba(110,231,255,.25);">
                                        <i class="fas fa-bookmark" style="color: rgba(245,247,255,.9);"></i>
                                    </div>
                                </div>
                                <div class="user_info">
                                    <span>Сохранённые</span>
                                    <p>Сообщения самому себе</p>
                                </div>
                            </div>
                        `;
                        contactsList.appendChild(favItem);
                        (groupsCache || []).forEach(g => {
                            const li = document.createElement('li');
                            li.setAttribute('onclick', `window.location.href='group_chat.php?id=${g.id}'`);
                            li.innerHTML = `
                                <div class="d-flex bd-highlight">
                                    <div class="img_cont">
                                        <div class="rounded-circle user_img" style="display:flex;align-items:center;justify-content:center;background:rgba(255,211,105,.15);border:1px solid rgba(255,211,105,.25);">
                                            <i class="fas fa-users" style="color: rgba(245,247,255,.9);"></i>
                                        </div>
                                    </div>
                                    <div class="user_info">
                                        <span>${(g.title || 'Группа').toString().replace(/</g,'&lt;').replace(/>/g,'&gt;')}</span>
                                        <p>Группа • участников: ${Number(g.members_count || 0)}</p>
                                    </div>
                                </div>
                            `;
                            contactsList.appendChild(li);
                        });
                        const totalUnread = data.data.reduce((sum, u) => sum + (Number(u.unread_messages) || 0), 0);
                        const prevUnread = Number(window.__prevUnread || 0);
                        if (totalUnread > prevUnread) {
                            try {
                                const ctx = window.__audioCtx || (window.__audioCtx = new (window.AudioContext || window.webkitAudioContext)());
                                const o = ctx.createOscillator();
                                const g = ctx.createGain();
                                o.type = 'sine';
                                o.frequency.value = 880;
                                g.gain.value = 0.02;
                                o.connect(g);
                                g.connect(ctx.destination);
                                o.start();
                                setTimeout(() => { o.stop(); }, 120);
                            } catch {}
                        }
                        window.__prevUnread = totalUnread;
                        data.data.forEach(user => {
                            const highlightedFullName = highlightSearchTerm(user.full_name, searchTerm);
                            const lastText = (user.last_message_content || '').toString();
                            const lastHasMedia = Number(user.last_message_has_media || 0) === 1;
                            const lastSenderId = Number(user.last_message_sender_id || 0);
                            let preview = '';
                            if (lastHasMedia && !lastText.trim()) {
                                preview = '📎 файл';
                            } else if (lastText.trim()) {
                                preview = lastText.replace(/\s+/g, ' ').trim();
                            } else {
                                preview = '@' + (user.username || '');
                            }
                            if (lastSenderId && lastSenderId === CURRENT_USER_ID) {
                                preview = 'Вы: ' + preview;
                            }
                            preview = truncatePreviewByWords(preview);
                            const highlightedPreview = highlightSearchTerm(preview, searchTerm);
                            const unreadMessages = user.unread_messages;
                            const listItem = document.createElement('li');
                            listItem.setAttribute('onclick', `window.location.href='chat.php?id=${user.user_id}'`);
                            listItem.innerHTML = `
                                <div class="d-flex bd-highlight">
                                    <div class="img_cont">
                                        <img src="./src/images/profile-picture/${user.profile_picture}"
                                            class="rounded-circle user_img"
                                            alt="${user.full_name}">
                                    </div>
                                    <div class="user_info">
                                        <span>${highlightedFullName}</span>
                                        <p>${highlightedPreview}</p>
                                    </div>
                                    <div class="message_count">
                                        ${unreadMessages > 0 ? `<span class="badge badge-warning">${unreadMessages}</span>` : ''}
                                    </div>
                                </div>
                            `;
                            contactsList.appendChild(listItem);
                        });
                        if (scrollBox) {
                            scrollBox.scrollTop = prevTop;
                        }
                    } else {
                        lastContactsSignature = '';
                        contactsList.innerHTML = '';
                        contactsEmpty.style.display = 'block';
                    }
                });
        }
        function highlightSearchTerm(text, searchTerm) {
            if (!searchTerm) return text;
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<span style="color: #e43c5a;">$1</span>');
        }
        fetchContacts();
        function logout() {
            Swal.fire({
                title: 'Вы уверены?',
                text: "Хотите выйти из профиля?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Да, выйти',
                cancelButtonText: 'Отмена'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = './logout/';
                }
            });
        }
    </script>
</body>
</html>