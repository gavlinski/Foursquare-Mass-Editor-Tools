<?php
/**
 * Script para criar uma sessão temporária para testes
 */

// Desabilita output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Inicia a sessão diretamente
session_start();

// Define um token temporário
$_SESSION['oauth_token'] = 'test_token_123';

// Define dados do usuário com estrutura completa (como retorna a API real)
$userData = [
    'firstName' => 'Elio',
    'lastName' => 'Gavlinski',
    'id' => '12345',
    'checkins' => ['count' => 1234],
    'photo' => ['prefix' => 'https://fastly.4sqi.net/img/user/']
];

$_SESSION['user_data'] = $userData;

// Define o cookie
$fullName = $userData['firstName'] . ' ' . $userData['lastName'];
setcookie("name", rawurlencode($fullName), time() + 60*60*24, "/", "", false, true);

echo "Sessão de teste criada!\n";
echo "Token: test_token_123\n";
echo "Usuário: " . $fullName . "\n";
echo "Session ID: " . session_id() . "\n";

// Força o flush
flush();
?>
