<?php
/**
 * Script temporário para testar a funcionalidade dos botões
 */

header('Content-Type: application/json');

// Simula uma resposta válida com dados de usuário
echo json_encode([
    'status' => 'valid',
    'message' => 'Sessão válida',
    'authenticated' => true,
    'timestamp' => time(),
    'user' => [
        'name' => 'Elio Gavlinski',
        'id' => '12345',
        'photo' => null,
        'checkins_count' => 1234
    ],
    'session_expires' => time() + (60 * 60 * 24 * 15) // 15 dias
]);
