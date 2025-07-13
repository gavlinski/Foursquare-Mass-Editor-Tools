<?php
// Script para simular login completo
session_start();

// Simula dados como retornados pela API real do Foursquare
$_SESSION['oauth_token'] = 'real_token_abc123';
$_SESSION['user_data'] = [
    'firstName' => 'Elio',
    'lastName' => 'Gavlinski',
    'id' => '54321',
    'checkins' => ['count' => 5678],
    'photo' => ['prefix' => 'https://fastly.4sqi.net/img/user/']
];

echo json_encode([
    'message' => 'Sessão simulada criada com dados completos',
    'session_id' => session_id(),
    'token' => $_SESSION['oauth_token'],
    'user' => $_SESSION['user_data']['firstName'] . ' ' . $_SESSION['user_data']['lastName']
]);
?>
