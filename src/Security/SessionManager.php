<?php

namespace ElioTools\Security;

class SessionManager
{
    private array $defaultOptions = [
        'cookie_secure' => false, // Will be set based on HTTPS
        'cookie_httponly' => true,
        'cookie_samesite' => 'Strict',
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
        
        // Define configurações baseadas no ambiente
        $isProduction = ($_ENV['APP_ENV'] ?? 'development') === 'production';
        $options['cookie_secure'] = $isProduction ? true : $this->isHttps();
        $options['cookie_httponly'] = filter_var($_ENV['SESSION_HTTPONLY'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $options['gc_maxlifetime'] = (int)($_ENV['SESSION_LIFETIME'] ?? 1440);

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

        $options = [
            'expires' => $expires ?: time() + 60*60*24*15,
            'path' => '/',
            'domain' => '',
            'secure' => filter_var($_ENV['COOKIE_SECURE'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'httponly' => filter_var($_ENV['COOKIE_HTTPONLY'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'samesite' => $_ENV['COOKIE_SAMESITE'] ?? 'Lax'
        ];
        
        setcookie($name, $value, $options);
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
}
