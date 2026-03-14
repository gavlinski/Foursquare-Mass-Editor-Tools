<?php

namespace ElioTools\Security;

class SessionManager
{
    private array $defaultOptions = [
        'cookie_secure' => false, // Will be set based on HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax', // Lax permite OAuth redirects (cross-site GET)
        'use_strict_mode' => true,
        'cookie_lifetime' => 0,
        'gc_maxlifetime' => 1440,
    ];

    public function start(array $options = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Inicia output buffering para prevenir problemas com headers
        if (!ob_get_level()) {
            ob_start();
        }

        $options = array_merge($this->defaultOptions, $options);
        
        // Define secure cookie apenas se estiver em HTTPS
        $options['cookie_secure'] = $this->isHttps();

        session_start($options);
        
        // Regenera ID da sessão para prevenir session fixation
        if (!isset($_SESSION['regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = true;
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
            session_unset();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function setCookie(string $name, string $value, int $expires = 0): void
    {
        // Verifica se headers já foram enviados
        if (headers_sent()) {
            error_log("Warning: Cannot set cookie '$name' - headers already sent");
            return;
        }

        // Configurações mais flexíveis para desenvolvimento
        $isDevEnvironment = ($_SERVER['HTTP_HOST'] ?? '') === 'localhost' || 
                           strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false ||
                           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

        $options = [
            'expires' => $expires ?: time() + 60*60*24*15,
            'path' => '/',
            'domain' => '',
            'secure' => $this->isHttps() && !$isDevEnvironment, // Não forçar HTTPS em dev
            'httponly' => false, // Permitir acesso JavaScript ao oauth_token
            'samesite' => 'Lax' // Lax permite OAuth redirects (cross-site GET) em dev e prod
        ];
        
        error_log("SessionManager: Setting cookie '$name' with options: " . json_encode($options));
        setcookie($name, $value, $options);
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}
