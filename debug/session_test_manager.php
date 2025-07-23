<?php
/**
 * Session Test Manager - Ferramenta consolidada para testes de sessão
 * Consolida funcionalidades de: create_debug.php, create_session.php, 
 * create_test_session_final.php, quick_session.php, temp_session.php
 */

// Desabilita output buffering para resposta imediata
if (ob_get_level()) {
    ob_end_clean();
}

$action = $_GET['action'] ?? 'create';
$format = $_GET['format'] ?? 'json';

function createTestSession($sessionType = 'default') {
    session_start();
    
    // Dados de teste padronizados
    $testData = [
        'oauth_token' => 'test_token_123',
        'user_data' => [
            'firstName' => 'Elio',
            'lastName' => 'Gavlinski',
            'id' => '12345',
            'checkins' => ['count' => 1234]
        ]
    ];
    
    // Aplicar dados na sessão
    foreach ($testData as $key => $value) {
        $_SESSION[$key] = $value;
    }
    
    // Define cookie para compatibilidade
    if (!headers_sent()) {
        setcookie("name", rawurlencode("Elio Gavlinski"), time() + 3600, "/");
    }
    
    return [
        'status' => 'success',
        'message' => 'Sessão de teste criada',
        'session_id' => session_id(),
        'session_type' => $sessionType,
        'data' => $testData
    ];
}

function destroyTestSession() {
    session_start();
    session_destroy();
    
    if (!headers_sent()) {
        setcookie("name", "", time() - 3600, "/");
    }
    
    return [
        'status' => 'success',
        'message' => 'Sessão destruída'
    ];
}

function getSessionStatus() {
    session_start();
    
    return [
        'status' => 'info',
        'session_id' => session_id(),
        'oauth_token' => $_SESSION['oauth_token'] ?? null,
        'user_data' => $_SESSION['user_data'] ?? null,
        'is_valid' => isset($_SESSION['oauth_token'])
    ];
}

// Processar ação
$result = [];
switch ($action) {
    case 'create':
        $result = createTestSession();
        break;
    case 'destroy':
        $result = destroyTestSession();
        break;
    case 'status':
        $result = getSessionStatus();
        break;
    default:
        $result = ['status' => 'error', 'message' => 'Ação inválida'];
}

// Resposta baseada no formato
header('Content-Type: application/json');
if ($format === 'simple') {
    echo $result['status'] === 'success' ? 'OK' : 'ERROR';
} else {
    echo json_encode($result, JSON_PRETTY_PRINT);
}
?>
