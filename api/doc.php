<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Документация API</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background:
        .container { background:
        h1 { font-size:2.2rem; font-weight:800; text-align:center; margin-bottom:28px; }
        .muted { color:
        code { color:
    </style>
</head>
<body>
    <div class="container">
        <h1>Документация API</h1>
        <p class="muted text-center mb-4">Короткий список эндпоинтов проекта (PHP + MySQL, без WebSocket).</p>
        <h3>Авторизация (никнейм + PIN)</h3>
        <ul class="list-group mb-4">
            <li class="list-group-item"><code>POST /api/auth/start.php</code> — проверить никнейм, вернуть шаг (создать PIN / ввести PIN)</li>
            <li class="list-group-item"><code>POST /api/auth/set_pin.php</code> — создать профиль (если новый) и сохранить PIN (хэш)</li>
            <li class="list-group-item"><code>POST /api/auth/login_pin.php</code> — вход по PIN</li>
            <li class="list-group-item"><code>POST /api/auth/logout.php</code> — выход из профиля</li>
            <li class="list-group-item"><code>SESSION /api/auth/check_login.php</code> — проверить, авторизован ли пользователь</li>
        </ul>
        <h3>Сообщения и контакты</h3>
        <ul class="list-group mb-4">
            <li class="list-group-item"><code>GET /api/fetch_contacts.php?search=...</code> — список контактов</li>
            <li class="list-group-item"><code>POST /api/fetch_messages.php</code> — сообщения с пользователем</li>
            <li class="list-group-item"><code>POST /api/send_message.php</code> — отправить сообщение</li>
            <li class="list-group-item"><code>POST /api/edit_message.php</code> — изменить сообщение</li>
            <li class="list-group-item"><code>POST /api/delete_message.php</code> — удалить сообщение</li>
            <li class="list-group-item"><code>POST /api/clear_messages.php</code> — очистить переписку</li>
        </ul>
        <h3>Профиль и блокировки</h3>
        <ul class="list-group">
            <li class="list-group-item"><code>GET/POST /api/fetch_profile.php</code> — получить/обновить профиль</li>
            <li class="list-group-item"><code>POST /api/change_user_status.php</code> — блок/разблок</li>
            <li class="list-group-item"><code>GET /api/check_user_status.php?receiver_id=...</code> — проверка блокировки</li>
        </ul>
        <p class="muted text-center mt-4 mb-0">© <?= date('Y') ?> Messengersd</p>
    </div>
</body>
</html>