<?php

/**
 * Foursquare Token Request
 *
 * Requisita um OAuth Token para autenticação do usuário
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    3.0.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/index.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

declare(strict_types=1);

// Analytics interno (privacy-friendly)
require_once __DIR__ . '/analytics.php';

// Carrega o autoloader do Composer
require_once __DIR__ . '/vendor/autoload.php';

// Inclui a classe FoursquareApi original
require_once __DIR__ . '/FoursquareAPI.Class.php';

// Inclui helper de assets
require_once __DIR__ . '/includes/asset_helper.php';

use ElioTools\Config\AppConfig;
use ElioTools\Security\SessionManager;

// Inicializa configurações
$config = new AppConfig();
$sessionManager = new SessionManager();
$sessionManager->start();

// Proteção contra loop de redirecionamento
$redirectCount = $sessionManager->get('redirect_count') ?? 0;
if ($redirectCount > 10) {
    error_log("LOOP DETECTADO! Limpando sessão e cookies...");
    $sessionManager->destroy();
    $sessionManager->setCookie("oauth_token", "", time() - 3600);
    unset($_COOKIE['oauth_token']);
    $sessionManager->remove('redirect_count');
    $redirectCount = 0;
}
$sessionManager->set('redirect_count', $redirectCount + 1);

// Inicializa API do Foursquare
$foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));

// Verifica se é uma requisição de logout
if (isset($_GET['logout'])) {
    $sessionManager->destroy();
    
    // Limpa o cookie da sessão PHP
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Limpa cookies da aplicação
    $sessionManager->setCookie("oauth_token", "", time() - 3600);
    $sessionManager->setCookie("name", "", time() - 3600);
    $sessionManager->setCookie("coordinates", "", time() - 3600);
    
    // Força limpeza da superglobal para o restante da execução
    unset($_COOKIE['oauth_token']);
    unset($_COOKIE['name']);
    unset($_COOKIE['coordinates']);
    
    header('Location: index.php');
    exit;
}

// Verifica se houve erro de autenticação (evita loop de redirecionamento)
if (isset($_GET['error']) && $_GET['error'] === 'auth_failed') {
    $sessionManager->destroy();
    $sessionManager->setCookie("oauth_token", "", time() - 3600);
    $sessionManager->setCookie("name", "", time() - 3600);
    $sessionManager->setCookie("coordinates", "", time() - 3600);
    // Remove o cookie da superglobal para não ser pego na lógica abaixo
    unset($_COOKIE['oauth_token']);
    $token = null; // Força token como null para mostrar tela de login
}

// Validação e obtenção do token
$token = null;
if (!isset($_GET['error']) && isset($_COOKIE['oauth_token']) && $_COOKIE['oauth_token'] !== "0" && !isset($_GET['code'])) {
    // Tem cookie mas não tem code - pode ser um loop
    // Tenta validar token existente
    $existingToken = filter_var($_COOKIE['oauth_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $foursquare->SetAccessToken($existingToken);
    try {
        $testResponse = $foursquare->GetPrivate("users/self");
        $testData = json_decode($testResponse, true);
        if (isset($testData['response']['user'])) {
            // Token válido - usar
            $token = $existingToken;
            error_log("index.php: Token do cookie validado com sucesso");
        } else {
            // Token inválido - limpar e pedir novo login
            error_log("index.php: Token do cookie inválido - limpando");
            $sessionManager->setCookie("oauth_token", "", time() - 3600);
            unset($_COOKIE['oauth_token']);
            $token = null;
        }
    } catch (Exception $e) {
        error_log("index.php: Erro ao validar token: " . $e->getMessage());
        $sessionManager->setCookie("oauth_token", "", time() - 3600);
        unset($_COOKIE['oauth_token']);
        $token = null;
    }
} elseif (isset($_GET['code'])) {
    $code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($code) {
        try {
            $token = $foursquare->GetToken($code, $config->get('redirect_uri'));
        } catch (Exception $e) {
            error_log("Erro ao obter token: " . $e->getMessage());
            header('Location: error.php');
            exit;
        }
    }
}

// Processa o token se existir
if ($token) {
    error_log("index.php: Token obtido = " . substr($token, 0, 20) . "...");
    error_log("index.php: Session ID antes de salvar = " . session_id());
    
    $sessionManager->set("oauth_token", $token);
    $sessionManager->setCookie("oauth_token", $token);
    
    error_log("index.php: Token salvo na sessão e cookie");
    error_log("index.php: Verificação - Session oauth_token = " . ($sessionManager->get('oauth_token') ? 'EXISTS' : 'NULL'));
    
    // Load the Foursquare API library
    $foursquare->SetAccessToken($token);

    try {
        // Perform a request to a authenticated-only resource
        $userDataResponse = $foursquare->GetPrivate("users/self");
        $userData = json_decode($userDataResponse, true);
        
        // Returns profile information for a given user
        $u = $userData['response']['user'] ?? null;
        if ($u && isset($u['firstName'], $u['lastName'])) {
            $name = htmlspecialchars($u['firstName'] . " " . $u['lastName']);
            $sessionManager->setCookie("name", rawurlencode($name), time() + 60*60*24);
        }
        
        if (isset($u['checkins']['items'][0]['venue']['location'])) {
            $location = $u['checkins']['items'][0]['venue']['location'];
            $coordinates = $location['lat'] . "," . $location['lng'];
            $sessionManager->setCookie("coordinates", $coordinates, time() + 60*60*24);
        }
    } catch (Exception $e) {
        error_log("Erro ao processar resposta da API: " . $e->getMessage());
        header('Location: error.php');
        exit;
    }
    
    // Força sincronização da sessão antes do redirect
    session_write_close();
    
    // Reset contador de redirects ao finalizar com sucesso
    $sessionManager->set('redirect_count', 0);
    
    if ($sessionManager->has("venues")) {
        header('Location: load.php');
        exit;
    } else {
        header('Location: main.php');
        exit;
    }
}

// Se não há token, mostra a página de autenticação

?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools - Foursquare Mass Editor</title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="Ferramenta para edição em massa de locais no Foursquare. Criada para placemakers para facilitar a visualização, importação, edição e sinalização de dados de múltiplos locais.">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<style>
:root {
    /* Dark theme (default) */
    --bg-primary: #0f0f1e;
    --bg-gradient-1: rgba(102, 126, 234, 0.15);
    --bg-gradient-2: rgba(118, 75, 162, 0.15);
    --text-primary: #ffffff;
    --text-secondary: #b0b0c0;
    --text-tertiary: #a0a0b0;
    --text-muted: #707080;
    --brand-color: #667eea;
    --card-bg: rgba(255, 255, 255, 0.03);
    --card-border: rgba(255, 255, 255, 0.08);
    --card-hover-bg: rgba(255, 255, 255, 0.05);
    --card-hover-border: rgba(102, 126, 234, 0.3);
    --footer-border: rgba(255, 255, 255, 0.08);
    --link-hover: #667eea;
}

@media (prefers-color-scheme: light) {
    :root {
        /* Light theme */
        --bg-primary: #f5f8fa;
        --bg-gradient-1: rgba(102, 126, 234, 0.05);
        --bg-gradient-2: rgba(118, 75, 162, 0.05);
        --text-primary: #2c3e50;
        --text-secondary: #5a6c7d;
        --text-tertiary: #7f8c8d;
        --text-muted: #95a5a6;
        --brand-color: #5851db;
        --card-bg: #ffffff;
        --card-border: #e0e6ed;
        --card-hover-bg: #f8f9fa;
        --card-hover-border: rgba(88, 81, 219, 0.4);
        --footer-border: #e0e6ed;
        --link-hover: #5851db;
    }
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    min-height: 100vh;
    background: var(--bg-primary);
    color: var(--text-primary);
    position: relative;
    overflow-x: hidden;
}

body:before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: 
        radial-gradient(circle at 20% 30%, var(--bg-gradient-1) 0%, transparent 50%),
        radial-gradient(circle at 80% 70%, var(--bg-gradient-2) 0%, transparent 50%);
    pointer-events: none;
    z-index: 0;
}

.container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 80px 40px;
    position: relative;
    z-index: 1;
}

.header {
    text-align: center;
    margin-bottom: 60px;
}

.brand {
    font-size: 16px;
    font-weight: 600;
    color: var(--brand-color);
    margin-bottom: 20px;
    letter-spacing: 2px;
    text-transform: uppercase;
}

h1 {
    font-size: 56px;
    font-weight: 800;
    margin-bottom: 20px;
    background: linear-gradient(135deg, #667eea 0%, #a991ff 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.tagline {
    font-size: 22px;
    color: var(--text-tertiary);
    margin-bottom: 40px;
    font-weight: 300;
}

.description {
    font-size: 17px;
    line-height: 1.8;
    color: var(--text-secondary);
    max-width: 700px;
    margin: 0 auto 50px;
}

.cta-section {
    text-align: center;
    margin-bottom: 60px;
}

.cta-button {
    display: inline-block;
    padding: 18px 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.cta-button:before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.1);
    transition: left 0.5s ease;
}

.cta-button:hover:before {
    left: 100%;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
    margin-bottom: 60px;
}

.feature-card {
    background: var(--card-bg);
    border: 1px solid var(--card-border);
    border-radius: 12px;
    padding: 30px;
    transition: all 0.3s ease;
}

.feature-card:hover {
    background: var(--card-hover-bg);
    border-color: var(--card-hover-border);
    transform: translateY(-5px);
}

.feature-icon {
    width: 48px;
    height: 48px;
    margin-bottom: 15px;
    color: var(--brand-color);
}

.feature-card h3 {
    font-size: 18px;
    margin-bottom: 10px;
    color: var(--text-primary);
}

.feature-card p {
    font-size: 14px;
    color: var(--text-secondary);
    line-height: 1.6;
}

.feature-card p a {
    color: var(--brand-color);
    text-decoration: none;
    border-bottom: 1px solid var(--card-border);
    transition: border-color 0.3s ease;
}

.feature-card p a:hover {
    border-bottom-color: var(--brand-color);
}

.footer {
    text-align: center;
    padding-top: 40px;
    border-top: 1px solid var(--footer-border);
}

.footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.footer-links a {
    color: var(--text-tertiary);
    text-decoration: none;
    font-size: 14px;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: var(--link-hover);
}

.disclaimer {
    font-size: 13px;
    color: var(--text-muted);
    line-height: 1.6;
    max-width: 700px;
    margin: 0 auto 30px auto;
}

@media (max-width: 768px) {
    h1 { 
        font-size: 40px; 
    }
    .container { 
        padding: 60px 20px; 
    }
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
<script>
// Remove fragmento #_=_ do OAuth (sem recarregar a página)
if (window.location.hash === '#_=_') {
    if (history.replaceState) {
        history.replaceState(null, null, window.location.href.split('#')[0]);
    } else {
        window.location.hash = '';
    }
}
</script>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="brand">Foursquare Mass Editor Tools</div>
        <h1>Elio Tools</h1>
        <div class="tagline">Ferramenta de Edição de Múltiplos Locais</div>
        <div class="description">
            Ferramenta para edição em massa de locais no Foursquare®. Criada para placemakers 
            para facilitar a visualização, importação, edição e sinalização de dados de múltiplos locais.
        </div>
    </div>

    <div class="cta-section">
        <a href="<?php echo $foursquare->AuthenticationLink($config->get('redirect_uri')); ?>" class="cta-button">
            Conectar com Foursquare
        </a>
    </div>

    <div class="features-grid">
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            <h3>Visualização</h3>
            <p>Interface intuitiva para visualizar e gerenciar dados de múltiplos locais simultaneamente.</p>
        </div>
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
            </svg>
            <h3>Importação</h3>
            <p>Carregue dados de locais via CSV, IDs ou URLs, páginas web ou integração com <a href="https://www.foursweep.com" target="_blank">4sweep</a>.</p>
        </div>
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
            </svg>
            <h3>Edição</h3>
            <p>Edite os dados de vários campos validando todas as informações antes de salvar as alterações.</p>
        </div>
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"></path>
                <line x1="4" y1="22" x2="4" y2="15"></line>
            </svg>
            <h3>Sinalização</h3>
            <p>Identifique e sinalize rapidamente locais fechados ou duplicados para revisão posterior.</p>
        </div>
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"></circle>
                <path d="M21 21l-4.35-4.35"></path>
                <circle cx="11" cy="11" r="3"></circle>
            </svg>
            <h3>Busca Geográfica</h3>
            <p>Busca avançada por coordenadas ou nomes com raio personalizável e filtros de categoria.</p>
        </div>
        <div class="feature-card">
            <svg class="feature-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <h3>Google Maps</h3>
            <p>Integração completa com Google Maps para visualização e edição das coordenadas dos locais.</p>
        </div>
    </div>

    <div class="footer">
        <div class="disclaimer">
            Esta ferramenta conecta-se à sua conta Foursquare através de OAuth2 seguro. 
            Respeitamos sua privacidade e coletamos apenas o mínimo necessário para operação. 
            Todas as operações utilizam a API oficial do Foursquare.
        </div>
        <div class="footer-links">
            <a href="https://foursquare.com/developer/" target="_blank">Developer Docs</a>
            <a href="privacy.php">Privacy Policy</a>
            <a href="https://status.foursquare.com/" target="_blank">API Status</a>
            <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools" target="_blank">GitHub Repository</a>
        </div>
    </div>
</div>
</body>
</html>
<?php


?>

