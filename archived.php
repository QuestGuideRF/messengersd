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
    <title>Архив</title>
    <link rel="icon" type="image/svg+xml" href="./favicons/favicon.svg">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="./src/css/style.css">
</head>
<body>
    <div class="container-fluid h-100">
        <div class="row justify-content-center h-100">
            <div class="col-md-8 col-xl-6 chat">
                <div class="card">
                    <div class="card-header d-flex">
                        <div class="input-group">
                            <span class="input-group-text menu_btn" onclick="window.location.href='./';" title="Назад">
                                <i class="fas fa-arrow-left"></i>
                            </span>
                            <input type="text" placeholder="Поиск..." name="search" id="search" class="form-control search">
                            <div class="input-group-prepend">
                                <span class="input-group-text search_btn"><i class="fas fa-search"></i></span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body contacts_body">
                        <ul class="contacts" id="contacts-list"></ul>
                        <div id="contacts-empty" class="text-center text-muted mt-5" style="display:none;">Архив пуст.</div>
                    </div>
                    <div class="card-footer"></div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let fetchInterval = setInterval(fetchContacts, 1500);
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
            fetchInterval = setInterval(fetchContacts, 1500);
        });
        function fetchContacts(searchTerm = '') {
            fetch('./api/fetch_contacts.php?archived=1&search=' + encodeURIComponent(searchTerm))
                .then(response => response.json())
                .then(data => {
                    const contactsList = document.getElementById('contacts-list');
                    const contactsEmpty = document.getElementById('contacts-empty');
                    contactsList.innerHTML = '';
                    if (data.status === 'success' && data.data.length > 0) {
                        contactsEmpty.style.display = 'none';
                        data.data.forEach(user => {
                            const listItem = document.createElement('li');
                            listItem.setAttribute('onclick', `window.location.href='chat.php?id=${user.user_id}'`);
                            listItem.innerHTML = `
                                <div class="d-flex bd-highlight">
                                    <div class="img_cont">
                                        <img src="./src/images/profile-picture/${user.profile_picture}" class="rounded-circle user_img" alt="${user.full_name}">
                                    </div>
                                    <div class="user_info">
                                        <span>${user.full_name}</span>
                                        <p>@${user.username}</p>
                                    </div>
                                </div>
                            `;
                            contactsList.appendChild(listItem);
                        });
                    } else {
                        contactsEmpty.style.display = 'block';
                    }
                });
        }
        fetchContacts();
    </script>
</body>
</html>