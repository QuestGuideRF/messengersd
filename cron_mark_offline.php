<?php
require_once __DIR__ . '/config.php';
$db = new Database();
$usersTable = $db->t('users');
$db->executeQuery(
    "UPDATE {$usersTable} SET is_online = 0 WHERE last_seen IS NOT NULL AND last_seen < (NOW() - INTERVAL 2 MINUTE)",
    [],
    ''
);
echo "OK\n";