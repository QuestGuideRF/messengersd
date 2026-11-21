<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Content-Type: application/json');
session_start();
http_response_code(410);
echo json_encode([
    'status' => 'error',
    'message' => 'Старая регистрация (email/пароль) отключена. Используйте вход по никнейму и PIN-коду.',
    'data' => []
]);