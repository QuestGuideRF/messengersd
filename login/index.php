<?php
require_once __DIR__ . '/../app/session.php';
startAppSession();
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("Location: ../");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="../favicons/favicon.svg">
    <title>Вход</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../src/css/login_signup.css">
</head>
<body>
    <div class="form-container">
        <h1>Вход</h1>
        <form id="stepUsername">
            <div class="form-group">
                <label for="username">Никнейм</label>
                <input type="text" id="username" name="username" required maxlength="30" autocomplete="username" inputmode="text" placeholder="Например: slava_01">
                <small id="username-error" style="color: #e43c5a; font-weight:600"></small>
            </div>
            <div class="form-group">
                <button type="submit" id="btn-username" disabled>Продолжить</button>
            </div>
        </form>
        <form id="stepCreatePin" style="display:none;">
            <div class="form-group">
                <label for="pin_new">Придумайте PIN</label>
                <input type="password" id="pin_new" name="pin" required inputmode="numeric" autocomplete="new-password" placeholder="••••" maxlength="4">
            </div>
            <div class="form-group">
                <label for="pin_confirm">Повторите PIN</label>
                <input type="password" id="pin_confirm" name="pin_confirm" required inputmode="numeric" autocomplete="new-password" placeholder="••••" maxlength="4">
                <small id="pin-create-error" style="color: #e43c5a; font-weight:600"></small>
            </div>
            <div class="form-group">
                <button type="submit" id="btn-create-pin">Сохранить PIN и войти</button>
            </div>
            <div class="text-center">
                <a href="#" id="back-to-username">Назад</a>
            </div>
        </form>
        <form id="stepEnterPin" style="display:none;">
            <div class="form-group">
                <label for="pin_enter">Введите PIN</label>
                <input type="password" id="pin_enter" name="pin" required inputmode="numeric" autocomplete="current-password" placeholder="••••" maxlength="6">
                <small id="pin-enter-error" style="color: #e43c5a; font-weight:600"></small>
            </div>
            <div class="form-group">
                <button type="submit" id="btn-enter-pin">Войти</button>
            </div>
            <div class="text-center">
                <a href="#" id="back-to-username-2">Сменить никнейм</a>
            </div>
        </form>
    </div>
    <script src="../src/js/sweetalert2.js"></script>
    <script>
        const stepUsername = document.getElementById('stepUsername');
        const stepCreatePin = document.getElementById('stepCreatePin');
        const stepEnterPin = document.getElementById('stepEnterPin');
        const usernameField = document.getElementById('username');
        const usernameError = document.getElementById('username-error');
        const btnUsername = document.getElementById('btn-username');
        const pinNew = document.getElementById('pin_new');
        const pinConfirm = document.getElementById('pin_confirm');
        const pinCreateError = document.getElementById('pin-create-error');
        const pinEnter = document.getElementById('pin_enter');
        const pinEnterError = document.getElementById('pin-enter-error');
        let currentUsername = '';
        function showStep(step) {
            stepUsername.style.display = (step === 'username') ? 'block' : 'none';
            stepCreatePin.style.display = (step === 'create_pin') ? 'block' : 'none';
            stepEnterPin.style.display = (step === 'enter_pin') ? 'block' : 'none';
        }
        function validateUsername() {
            const username = (usernameField.value || '').trim().toLowerCase();
            const usernamePattern = /^[a-z0-9_]{3,30}$/;
            if (username.length === 0) {
                usernameError.textContent = '';
                btnUsername.disabled = true;
                return;
            }
            if (!usernamePattern.test(username)) {
                usernameError.textContent = 'Никнейм: латинские буквы/цифры/подчёркивание, 3–30 символов';
                btnUsername.disabled = true;
                return;
            }
            usernameError.textContent = '';
            btnUsername.disabled = false;
        }
        usernameField.addEventListener('input', validateUsername);
        function onlyDigits(el, maxLen) {
            el.addEventListener('input', () => {
                el.value = (el.value || '').replace(/\D+/g, '').slice(0, maxLen);
            });
        }
        onlyDigits(pinNew, 4);
        onlyDigits(pinConfirm, 4);
        onlyDigits(pinEnter, 4);
        stepUsername.addEventListener('submit', async (e) => {
            e.preventDefault();
            const username = (usernameField.value || '').trim().toLowerCase();
            currentUsername = username;
            const formData = new FormData();
            formData.append('username', username);
            try {
                const res = await fetch('../api/auth/start.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status !== 'success') {
                    Swal.fire({ icon: 'error', title: 'Ошибка', text: data.message || 'Не удалось продолжить' });
                    return;
                }
                if (data.data && data.data.state === 'create_pin') {
                    pinNew.value = '';
                    pinConfirm.value = '';
                    pinCreateError.textContent = '';
                    showStep('create_pin');
                    pinNew.focus();
                } else {
                    pinEnter.value = '';
                    pinEnterError.textContent = '';
                    showStep('enter_pin');
                    pinEnter.focus();
                }
            } catch {
                Swal.fire({ icon: 'error', title: 'Ошибка сети', text: 'Не удалось подключиться к серверу. Попробуйте позже.' });
            }
        });
        document.getElementById('back-to-username').addEventListener('click', (e) => {
            e.preventDefault();
            showStep('username');
            usernameField.focus();
        });
        document.getElementById('back-to-username-2').addEventListener('click', (e) => {
            e.preventDefault();
            showStep('username');
            usernameField.focus();
        });
        stepCreatePin.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin = (pinNew.value || '').trim();
            const confirm = (pinConfirm.value || '').trim();
            pinCreateError.textContent = '';
            if (pin.length !== 4 || confirm.length !== 4) {
                pinCreateError.textContent = 'PIN должен состоять из 4 цифр';
                return;
            }
            if (pin !== confirm) {
                pinCreateError.textContent = 'PIN-коды не совпадают';
                return;
            }
            const formData = new FormData();
            formData.append('username', currentUsername);
            formData.append('pin', pin);
            formData.append('pin_confirm', confirm);
            try {
                const res = await fetch('../api/auth/set_pin.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Готово', text: data.message, showConfirmButton: false, timer: 1200 })
                        .then(() => window.location.href = '../');
                } else {
                    Swal.fire({ icon: 'error', title: 'Ошибка', text: data.message || 'Не удалось сохранить PIN' });
                }
            } catch {
                Swal.fire({ icon: 'error', title: 'Ошибка сети', text: 'Не удалось подключиться к серверу. Попробуйте позже.' });
            }
        });
        stepEnterPin.addEventListener('submit', async (e) => {
            e.preventDefault();
            const pin = (pinEnter.value || '').trim();
            pinEnterError.textContent = '';
            if (pin.length !== 4) {
                pinEnterError.textContent = 'PIN должен состоять из 4 цифр';
                return;
            }
            const formData = new FormData();
            formData.append('username', currentUsername);
            formData.append('pin', pin);
            try {
                const res = await fetch('../api/auth/login_pin.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.status === 'success') {
                    window.location.href = '../';
                } else {
                    pinEnterError.textContent = data.message || 'Не удалось войти';
                }
            } catch {
                Swal.fire({ icon: 'error', title: 'Ошибка сети', text: 'Не удалось подключиться к серверу. Попробуйте позже.' });
            }
        });
        showStep('username');
        validateUsername();
    </script>
</body>
</html>