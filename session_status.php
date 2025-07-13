<?php

/**
 * Verificação de Status da Sessão
 * 
 * Endpoint para verificar se a sessão e token estão válidos
 */

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Carrega o autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Inclui a classe FoursquareApi original
require_once __DIR__ . '/FoursquareAPI.Class.php';

use ElioTools\Config\AppConfig;
use ElioTools\Security\SessionManager;

try {
    // Inicializa sessão nativa primeiro
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Verifica se existe token
    $token = $_SESSION['oauth_token'] ?? $_COOKIE['oauth_token'] ?? null;
    
    // Debug log detalhado para diagnosticar problemas
    error_log("session_status.php: Session token = " . var_export($_SESSION['oauth_token'] ?? 'NOT_SET', true));
    error_log("session_status.php: Cookie token = " . var_export($_COOKIE['oauth_token'] ?? 'NOT_SET', true));
    error_log("session_status.php: Final token = " . var_export($token, true));
    error_log("session_status.php: Session ID = " . session_id());
    error_log("session_status.php: Available cookies: " . json_encode(array_keys($_COOKIE)));
    
    if (!$token || $token === "0" || $token === "" || $token === false) {
        error_log("session_status.php: Token inválido ou vazio - motivo: " . 
                 (!$token ? 'null/empty' : ($token === "0" ? 'zero_string' : 'false_or_empty')));
        echo json_encode([
            'status' => 'expired',
            'message' => 'Token não encontrado',
            'authenticated' => false,
            'timestamp' => time(),
            'redirect_url' => 'index.php',
            'debug' => [
                'session_token' => isset($_SESSION['oauth_token']) ? 'SET' : 'NOT_SET',
                'cookie_token' => isset($_COOKIE['oauth_token']) ? 'SET' : 'NOT_SET',
                'session_id' => session_id(),
                'available_cookies' => array_keys($_COOKIE)
            ]
        ]);
        exit;
    }

    // Verifica se há dados de usuário na sessão (para casos de sessão existente)
    $userData = $_SESSION['user_data'] ?? null;
    if ($userData && is_array($userData)) {
        $firstName = $userData['firstName'] ?? '';
        $lastName = $userData['lastName'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        
        if ($fullName) {
            setcookie("name", rawurlencode($fullName), time() + 60*60*24, "/");
        }
        
        echo json_encode([
            'status' => 'valid',
            'message' => 'Sessão válida (cache)',
            'authenticated' => true,
            'timestamp' => time(),
            'user' => [
                'name' => $fullName,
                'id' => $userData['id'] ?? '',
                'photo' => $userData['photo']['prefix'] ?? null,
                'checkins_count' => $userData['checkins']['count'] ?? 0
            ],
            'session_expires' => time() + (60 * 60 * 24 * 15) // 15 dias
        ]);
        exit;
    }

    // Inicializa configurações para API real
    $config = new AppConfig();
    
    // Testa o token com a API
    $foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));
    $foursquare->SetAccessToken($token);

    try {
        // Se for um token de teste, simula resposta
        if (strpos($token, 'test_token') === 0) {
            $userData = $sessionManager->get('user_data');
            if ($userData) {
                $user = $userData;
                // Atualiza informações na sessão
                $sessionManager->set('user_data', $user);
                $firstName = $user['firstName'] ?? '';
                $lastName = $user['lastName'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if ($fullName) {
                    $sessionManager->setCookie("name", rawurlencode($fullName), time() + 60*60*24);
                }
                
                echo json_encode([
                    'status' => 'valid',
                    'message' => 'Sessão válida (teste)',
                    'authenticated' => true,
                    'timestamp' => time(),
                    'user' => [
                        'name' => $fullName,
                        'id' => $user['id'],
                        'photo' => $user['photo']['prefix'] ?? null,
                        'checkins_count' => $user['checkins']['count'] ?? 0
                    ],
                    'session_expires' => time() + (60 * 60 * 24 * 15) // 15 dias
                ]);
                exit;
            }
        }
        
        $userDataResponse = $foursquare->GetPrivate("users/self");
        $userData = json_decode($userDataResponse, true);
        
        if (isset($userData['response']['user'])) {
            $user = $userData['response']['user'];
            
            // Atualiza informações na sessão
            $_SESSION['user_data'] = $user;
            $firstName = $user['firstName'] ?? '';
            $lastName = $user['lastName'] ?? '';
            $fullName = trim($firstName . ' ' . $lastName);
            if ($fullName) {
                setcookie("name", rawurlencode($fullName), time() + 60*60*24, "/");
            }
            
            echo json_encode([
                'status' => 'valid',
                'message' => 'Sessão válida',
                'authenticated' => true,
                'timestamp' => time(),
                'user' => [
                    'name' => $fullName,
                    'id' => $user['id'],
                    'photo' => $user['photo']['prefix'] ?? null,
                    'checkins_count' => $user['checkins']['count'] ?? 0
                ],
                'session_expires' => time() + (60 * 60 * 24 * 15) // 15 dias
            ]);
        } else {
            throw new Exception('Resposta inválida da API');
        }
    } catch (Exception $e) {
        // Token inválido ou expirado - limpa dados locais
        session_destroy();
        setcookie("oauth_token", "", time() - 3600, "/");
        setcookie("name", "", time() - 3600, "/");
        
        echo json_encode([
            'status' => 'expired',
            'message' => 'Token expirado ou inválido',
            'authenticated' => false,
            'timestamp' => time(),
            'error' => $e->getMessage(),
            'redirect_url' => 'index.php'
        ]);
    }

} catch (Exception $e) {
    error_log("Erro no session_status.php: " . $e->getMessage());
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno: ' . $e->getMessage(),
        'authenticated' => false,
        'error' => $e->getMessage()
    ]);
}
