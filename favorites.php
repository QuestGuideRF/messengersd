<?php
require_once __DIR__ . '/app/session.php';
startAppSession();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: ./login/");
    exit;
}
header("Location: ./saved_chat.php");
exit;