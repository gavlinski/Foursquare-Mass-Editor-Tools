<?php

/**
 * Middleware de Validação de Sessão
 * 
 * Valida automaticamente a sessão em requisições AJAX
 */

declare(strict_types=1);

use ElioTools\Config\AppConfig;
use ElioTools\Security\SessionManager;

class SessionValidationMiddleware {
    
    public static function validateSession(): array {
        // Carrega o autoloader do Composer
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
            // Inclui a classe FoursquareApi original
            require_once __DIR__ . '/FoursquareAPI.Class.php';

            try {
                $config = new AppConfig();
                $sessionManager = new SessionManager();
                $sessionManager->start();

                $token = $sessionManager->get('oauth_token') ?? $_COOKIE['oauth_token'] ?? null;
                
                if (!$token || $token === "0") {
                    return [
                        'valid' => false,
                        'error' => 'Token não encontrado',
                        'action' => 'redirect_to_login'
                    ];
                }

                // Testa o token com a API
                $foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));
                $foursquare->SetAccessToken($token);

                $userDataResponse = $foursquare->GetPrivate("users/self");
                $userData = json_decode($userDataResponse, true);
                
                if (!isset($userData['response']['user'])) {
                    throw new Exception('Resposta inválida da API');
                }

                return [
                    'valid' => true,
                    'user' => $userData['response']['user']
                ];

            } catch (Exception $e) {
                return [
                    'valid' => false,
                    'error' => $e->getMessage(),
                    'action' => 'redirect_to_login'
                ];
            }
        } else {
            // Fallback para sistema legado
            if (!isset($_SESSION)) {
                session_start();
            }
            
            $token = $_SESSION["oauth_token"] ?? $_COOKIE['oauth_token'] ?? null;
            
            if (!$token || $token === "0") {
                return [
                    'valid' => false,
                    'error' => 'Token não encontrado',
                    'action' => 'redirect_to_login'
                ];
            }

            return ['valid' => true];
        }
    }

    public static function requireValidSession(): void {
        $validation = self::validateSession();
        
        if (!$validation['valid']) {
            // Se for uma requisição AJAX, retorna JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode([
                    'error' => 'Sessão expirada',
                    'message' => $validation['error'],
                    'action' => 'redirect_to_login'
                ]);
                exit;
            } else {
                // Se for uma requisição normal, redireciona
                header('Location: index.php');
                exit;
            }
        }
    }

    public static function addSessionHeaders(): void {
        $validation = self::validateSession();
        
        if ($validation['valid']) {
            header('X-Session-Status: valid');
            if (isset($validation['user'])) {
                header('X-User-Name: ' . $validation['user']['firstName'] . ' ' . $validation['user']['lastName']);
            }
        } else {
            header('X-Session-Status: invalid');
        }
    }
}

// Se este arquivo for chamado diretamente, retorna o status da sessão
if (basename($_SERVER['PHP_SELF']) === 'session_middleware.php') {
    header('Content-Type: application/json');
    $result = SessionValidationMiddleware::validateSession();
    echo json_encode($result);
}
