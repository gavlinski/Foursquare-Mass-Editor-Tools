<?php
/**
 * Debug Session Validator - Ferramenta consolidada para validação de sessão
 * Consolida funcionalidades de: test_session.php, test_complete_session.php
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

$mode = $_GET['mode'] ?? 'validate';

function validateCurrentSession() {
    session_start();
    
    $isValid = isset($_SESSION['oauth_token']) && !empty($_SESSION['oauth_token']);
    $userData = $_SESSION['user_data'] ?? [];
    
    return [
        'status' => $isValid ? 'valid' : 'invalid',
        'message' => $isValid ? 'Sessão válida' : 'Sessão inválida ou expirada',
        'authenticated' => $isValid,
        'timestamp' => time(),
        'session_id' => session_id(),
        'user' => [
            'name' => ($userData['firstName'] ?? '') . ' ' . ($userData['lastName'] ?? ''),
            'id' => $userData['id'] ?? null,
            'photo' => $userData['photo'] ?? null,
            'checkins_count' => $userData['checkins']['count'] ?? 0
        ],
        'token_present' => isset($_SESSION['oauth_token']),
        'session_expires' => time() + (60 * 60 * 24 * 15) // 15 dias
    ];
}

function simulateValidSession() {
    session_start();
    
    // Simula dados como retornados pela API real do Foursquare
    $_SESSION['oauth_token'] = 'simulated_token_' . bin2hex(random_bytes(8));
    $_SESSION['user_data'] = [
        'firstName' => 'Elio',
        'lastName' => 'Gavlinski',
        'id' => '54321',
        'checkins' => ['count' => 5678],
        'photo' => ['prefix' => 'https://fastly.4sqi.net/img/user/']
    ];
    
    return [
        'status' => 'valid',
        'message' => 'Sessão simulada criada com dados completos',
        'authenticated' => true,
        'timestamp' => time(),
        'session_id' => session_id(),
        'token' => $_SESSION['oauth_token'],
        'user' => [
            'name' => $_SESSION['user_data']['firstName'] . ' ' . $_SESSION['user_data']['lastName'],
            'id' => $_SESSION['user_data']['id'],
            'photo' => $_SESSION['user_data']['photo'],
            'checkins_count' => $_SESSION['user_data']['checkins']['count']
        ],
        'session_expires' => time() + (60 * 60 * 24 * 15)
    ];
}

function getDebugInfo() {
    session_start();
    
    return [
        'status' => 'debug',
        'session_info' => [
            'session_id' => session_id(),
            'session_status' => session_status(),
            'session_data' => $_SESSION
        ],
        'server_info' => [
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get()
        ],
        'headers_sent' => headers_sent(),
        'cookies' => $_COOKIE
    ];
}

// Processar modo
$result = [];
switch ($mode) {
    case 'validate':
        $result = validateCurrentSession();
        break;
    case 'simulate':
        $result = simulateValidSession();
        break;
    case 'debug':
        $result = getDebugInfo();
        break;
    default:
        $result = ['status' => 'error', 'message' => 'Modo inválido'];
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>