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
<title>Elio Tools</title>
<meta charset="utf-8">
<?php dojo_script(['parseOnLoad' => true]); ?>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<?php dojo_theme('tundra'); ?>
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
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
<body class="tundra">
<p>
	<?php

		echo "<a href='" . $foursquare->AuthenticationLink($config->get('redirect_uri')) . "'><img src='img/connectTo@2x-f07c1cb7c6ed8894bb14dedd1001bcf3.png' alt='Connect to this app via Foursquare'></a>";

	?>
</p>
</body>
</html>
<?php


?>

