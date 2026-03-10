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
}
