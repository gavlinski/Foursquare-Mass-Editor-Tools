<?php

namespace ElioTools\Security;

use ElioTools\Config\AppConfig;

class SessionManager
{
    private AppConfig $config;

    private array $defaultOptions = [
        'use_strict_mode' => true,
        'use_only_cookies' => true,
        'cookie_path' => '/',
        'cookie_lifetime' => 0,
        'gc_maxlifetime' => 1440,
    ];

    public function __construct(?AppConfig $config = null)
    {
        $this->config = $config ?? new AppConfig();
    }

    public function start(array $options = []): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Inicia output buffering para prevenir problemas com headers
        if (!ob_get_level()) {
            ob_start();
        }

        $options = array_merge(
            $this->defaultOptions,
            [
                'cookie_secure' => $this->shouldUseSecureCookies((bool) $this->config->get('session_secure', true)),
                'cookie_httponly' => (bool) $this->config->get('session_httponly', true),
                'cookie_samesite' => $this->normalizeSameSite((string) $this->config->get('cookie_samesite', 'Lax')),
                'gc_maxlifetime' => (int) $this->config->get('session_lifetime', 1440),
            ],
            $options
        );

        session_start($options);

        $this->restoreTokenFromCookie();
        
        // Regenera ID da sessão para prevenir session fixation
        if (!isset($_SESSION['regenerated'])) {
            session_regenerate_id(true);
            $_SESSION['regenerated'] = true;
        }
    }

    public function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
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

    public function getAccessToken(): ?string
    {
        $token = $this->get('oauth_token');

        if ($this->isValidTokenValue($token)) {
            return (string) $token;
        }

        return $this->restoreTokenFromCookie();
    }

    public function restoreTokenFromCookie(string $sessionKey = 'oauth_token', string $cookieName = 'oauth_token'): ?string
    {
        $token = $_COOKIE[$cookieName] ?? null;

        if (!$this->isValidTokenValue($token)) {
            return null;
        }

        $_SESSION[$sessionKey] = $token;

        return (string) $token;
    }

    public function setCookie(string $name, string $value, int $expires = 0, array $overrides = []): void
    {
        // Verifica se headers já foram enviados
        if (headers_sent()) {
            error_log("Warning: Cannot set cookie '$name' - headers already sent");
            return;
        }

        $options = array_merge($this->getCookieOptions($expires), $overrides);
        
        error_log("SessionManager: Setting cookie '$name' with options: " . json_encode($options));
        setcookie($name, $value, $options);

        if (($options['expires'] ?? 0) < time()) {
            unset($_COOKIE[$name]);
            return;
        }

        $_COOKIE[$name] = $value;
    }

    public function expireCookie(string $name, array $overrides = []): void
    {
        $this->setCookie($name, '', time() - 3600, $overrides);
    }

    private function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
               $_SERVER['SERVER_PORT'] == 443 ||
               (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }

    private function shouldUseSecureCookies(bool $preferSecure): bool
    {
        return $preferSecure ? $this->isHttps() : false;
    }

    private function getCookieOptions(int $expires = 0): array
    {
        return [
            'expires' => $expires ?: time() + (60 * 60 * 24 * 15),
            'path' => '/',
            'domain' => '',
            'secure' => $this->shouldUseSecureCookies((bool) $this->config->get('cookie_secure', true)),
            'httponly' => false,
            'samesite' => $this->normalizeSameSite((string) $this->config->get('cookie_samesite', 'Lax')),
        ];
    }

    private function normalizeSameSite(string $value): string
    {
        $normalized = ucfirst(strtolower(trim($value)));

        return in_array($normalized, ['Lax', 'Strict', 'None'], true) ? $normalized : 'Lax';
    }

    private function isValidTokenValue(mixed $token): bool
    {
        return is_string($token) && $token !== '' && $token !== '0' && $token !== 'undefined' && $token !== 'null';
    }
}
