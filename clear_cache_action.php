<?php
/**
 * Clear Cache - Limpa completamente cache do navegador e sessões antigas
 * Usado para resolver problemas de dados antigos armazenados
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: application/json');

try {
    // Inicia sessão se não estiver ativa
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Limpa todas as variáveis de sessão
    $_SESSION = array();
    
    // Limpa cookies antigos
    $cookiesToClear = [
        'oauth_token',
        'name', 
        'PHPSESSID',
        'foursquare_token',
        'user_data',
        'session_data'
    ];
    
    foreach ($cookiesToClear as $cookie) {
        if (isset($_COOKIE[$cookie])) {
            setcookie($cookie, '', time() - 3600, '/');
            setcookie($cookie, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
            setcookie($cookie, '', time() - 3600, '/', '.' . $_SERVER['HTTP_HOST']);
        }
    }
    
    // Destrói a sessão completamente
    session_destroy();
    
    // Força headers para limpar cache
    header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Cache e dados antigos limpos com sucesso',
        'timestamp' => time(),
        'actions' => [
            'cookies_cleared' => count($cookiesToClear),
            'session_destroyed' => true,
            'cache_cleared' => true
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao limpar cache: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro ao limpar cache: ' . $e->getMessage(),
        'timestamp' => time()
    ]);
}
?>
