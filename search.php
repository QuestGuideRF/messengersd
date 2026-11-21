<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ./login/");
    exit;
}
$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск</title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="./src/css/style.css">
    <style>
        .search-page{max-width:900px;margin:0 auto;}
        .search-box{display:flex;gap:10px;align-items:center;margin-bottom:14px;}
        .result-card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:12px 14px;margin-bottom:10px;}
        .result-card .muted{color:rgba(245,247,255,.65);font-size:12px;}
        .result-card a{color:inherit;text-decoration:none;}
        .result-card a:hover{text-decoration:underline;}
    </style>
</head>
<body>
    <div class="container-fluid h-100">
        <div class="search-page">
            <div class="d-flex align-items-center mb-3">
                <button type="button" class="btn btn-link p-0 mr-3 text-light" onclick="window.location.href='./';" title="Назад">
                    ←
                </button>
                <h3 class="m-0">Поиск</h3>
            </div>
            <form class="search-box" method="GET" action="./search.php" autocomplete="off">
                <input class="form-control search" type="text" name="q" placeholder="Искать по сообщениям и контактам..." value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
                <button class="btn btn-primary" type="submit" style="border-radius:14px;font-weight:900;">Найти</button>
            </form>
            <div id="results"></div>
        </div>
    </div>
    <script>
        const query = <?= json_encode($q, JSON_UNESCAPED_UNICODE) ?>;
        const resultsDiv = document.getElementById('results');
        function renderEmpty() {
            resultsDiv.innerHTML = `<div class="result-card"><div class="muted">Введите запрос для поиска.</div></div>`;
        }
        function render(data) {
            const contacts = data.contacts || [];
            const messages = data.messages || [];
            let html = '';
            if (contacts.length) {
                html += `<h5 class="mt-3">Контакты</h5>`;
                contacts.forEach(c => {
                    html += `
                        <div class="result-card">
                            <a href="./chat.php?id=${c.user_id}">
                                <div><b>${c.full_name}</b> <span class="muted">@${c.username}</span></div>
                            </a>
                        </div>
                    `;
                });
            }
            if (messages.length) {
                html += `<h5 class="mt-3">Сообщения</h5>`;
                messages.forEach(m => {
                    html += `
                        <div class="result-card">
                            <a href="./chat.php?id=${m.peer_id}">
                                <div class="muted">Чат: @${m.peer_username}</div>
                                <div>${m.snippet}</div>
                            </a>
                        </div>
                    `;
                });
            }
            if (!contacts.length && !messages.length) {
                html = `<div class="result-card"><div class="muted">Ничего не найдено.</div></div>`;
            }
            resultsDiv.innerHTML = html;
        }
        if (!query) {
            renderEmpty();
        } else {
            fetch('./api/search.php?q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(res => {
                    if (res.status === 'success') {
                        render(res.data);
                    } else {
                        resultsDiv.innerHTML = `<div class="result-card"><div class="muted">${res.message || 'Ошибка поиска'}</div></div>`;
                    }
                })
                .catch(() => {
                    resultsDiv.innerHTML = `<div class="result-card"><div class="muted">Не удалось выполнить поиск. Попробуйте позже.</div></div>`;
                });
        }
    </script>
</body>
</html>