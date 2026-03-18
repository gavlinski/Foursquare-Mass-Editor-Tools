<?php

namespace ElioTools\Config;

use Dotenv\Dotenv;

class AppConfig
{
    private array $config;

    public function __construct()
    {
        // Carrega variáveis de ambiente
        if (file_exists(__DIR__ . '/../../.env')) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../..');
            $dotenv->load();
        }

        // Fallback para o arquivo de credenciais legado
        if (file_exists(__DIR__ . '/../../includes/app_credentials.php')) {
            require_once __DIR__ . '/../../includes/app_credentials.php';
        }

        $this->config = [
            'client_key' => $_ENV['FOURSQUARE_CLIENT_KEY'] ?? $client_key ?? '',
            'client_secret' => $_ENV['FOURSQUARE_CLIENT_SECRET'] ?? $client_secret ?? '',
            'redirect_uri' => $_ENV['FOURSQUARE_REDIRECT_URI'] ?? $redirect_uri ?? 'https://localhost/4sqmet',
            'app_env' => $_ENV['APP_ENV'] ?? 'production',
            'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'app_url' => $_ENV['APP_URL'] ?? 'https://localhost/4sqmet',
            'session_lifetime' => $this->getEnvInt('SESSION_LIFETIME', 1440),
            'session_secure' => $this->getEnvBool('SESSION_SECURE', true),
            'session_httponly' => $this->getEnvBool('SESSION_HTTPONLY', true),
            'cookie_secure' => $this->getEnvBool('COOKIE_SECURE', true),
            'cookie_httponly' => $this->getEnvBool('COOKIE_HTTPONLY', true),
            'cookie_samesite' => $this->normalizeSameSite($_ENV['COOKIE_SAMESITE'] ?? 'Lax'),
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->config;
    }

    private function getEnvBool(string $key, bool $default): bool
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $parsed ?? $default;
    }

    private function getEnvInt(string $key, int $default): int
    {
        $value = $_ENV[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        $parsed = filter_var($value, FILTER_VALIDATE_INT);

        return $parsed === false ? $default : $parsed;
    }

    private function normalizeSameSite(string $value): string
    {
        $normalized = ucfirst(strtolower(trim($value)));

        return in_array($normalized, ['Lax', 'Strict', 'None'], true) ? $normalized : 'Lax';
    }
}
