<?php
// Cria sessão de teste
session_start();

// Define dados de teste na sessão
$_SESSION['oauth_token'] = 'test_token_123';
$_SESSION['user_data'] = [
    'firstName' => 'Elio',
    'lastName' => 'Gavlinski',
    'id' => '12345',
    'checkins' => ['count' => 1234]
];

// Define cookie
setcookie("name", rawurlencode("Elio Gavlinski"), time() + 3600, "/");

echo json_encode([
    'status' => 'success',
    'message' => 'Sessão de teste criada',
    'session_id' => session_id()
]);
?>
