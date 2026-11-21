<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ./login/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Группы</title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="./src/css/style.css">
    <style>
        .panel{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:14px;margin-bottom:12px;}
    </style>
</head>
<body>
    <div class="container-fluid h-100">
        <div style="max-width:900px;margin:0 auto;">
            <div class="d-flex align-items-center mb-3 mt-3">
                <button type="button" class="btn btn-link p-0 mr-3 text-light" onclick="window.location.href='./';" title="Назад">←</button>
                <h3 class="m-0">Группы</h3>
            </div>
            <div class="panel">
                <h5>Создать группу</h5>
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" id="g_title" class="form-control" maxlength="80" placeholder="Например: Друзья">
                </div>
                <div class="form-group">
                    <label>Участники (никнеймы через запятую, опционально)</label>
                    <input type="text" id="g_members" class="form-control" placeholder="user1, user2">
                </div>
                <button class="btn btn-primary" id="g_create" style="border-radius:14px;font-weight:900;">Создать</button>
                <div id="g_err" class="mt-2" style="color:#e43c5a;font-weight:800;"></div>
            </div>
            <div class="panel">
                <h5>Мои группы</h5>
                <div id="groups"></div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const groupsDiv = document.getElementById('groups');
        function loadGroups() {
            fetch('./api/groups_list.php')
                .then(r => r.json())
                .then(res => {
                    if (res.status !== 'success') {
                        groupsDiv.innerHTML = `<div style="opacity:.7;">Не удалось загрузить группы.</div>`;
                        return;
                    }
                    const list = res.data || [];
                    if (!list.length) {
                        groupsDiv.innerHTML = `<div style="opacity:.7;">Пока нет групп.</div>`;
                        return;
                    }
                    groupsDiv.innerHTML = list.map(g => `
                        <div class="d-flex align-items-center justify-content-between" style="padding:10px 0;border-bottom:1px solid rgba(255,255,255,.08);">
                            <div>
                                <div style="font-weight:900;">${g.title}</div>
                                <div style="opacity:.7;font-size:12px;">Участников: ${g.members_count}</div>
                            </div>
                            <button class="btn btn-outline-light btn-sm" style="border-radius:12px;" onclick="window.location.href='./group_chat.php?id=${g.id}'">Открыть</button>
                        </div>
                    `).join('');
                })
                .catch(() => groupsDiv.innerHTML = `<div style="opacity:.7;">Не удалось загрузить группы.</div>`);
        }
        document.getElementById('g_create').addEventListener('click', () => {
            const title = (document.getElementById('g_title').value || '').trim();
            const members = (document.getElementById('g_members').value || '').trim();
            const err = document.getElementById('g_err');
            err.textContent = '';
            if (!title) { err.textContent = 'Введите название группы'; return; }
            const fd = new FormData();
            fd.append('title', title);
            fd.append('members', members);
            fetch('./api/groups_create.php', { method:'POST', body: fd })
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        Swal.fire({ icon:'success', title:'Группа создана', timer:900, showConfirmButton:false })
                            .then(() => window.location.href = './group_chat.php?id=' + res.data.group_id);
                    } else {
                        err.textContent = res.message || 'Ошибка';
                    }
                })
                .catch(() => err.textContent = 'Ошибка сети');
        });
        loadGroups();
    </script>
</body>
</html>