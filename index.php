<?php

/**
 * Foursquare Token Request
 *
 * Requisita um OAuth Token para autenticação do usuário
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2014
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

use ElioTools\Config\AppConfig;
use ElioTools\Security\SessionManager;

// Inicializa configurações
$config = new AppConfig();
$sessionManager = new SessionManager();
$sessionManager->start();

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
}

// Validação e obtenção do token
$token = null;
if (isset($_COOKIE['oauth_token']) && $_COOKIE['oauth_token'] !== "0") {
    $token = filter_var($_COOKIE['oauth_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
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
    $sessionManager->set("oauth_token", $token);
    $sessionManager->setCookie("oauth_token", $token);
    
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
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
<script>
// Remove fragmento #_=_ do OAuth e recarrega a página
if (window.location.hash === '#_=_') {
    if (history.replaceState) {
        history.replaceState(null, null, window.location.href.split('#')[0]);
    } else {
        window.location.hash = '';
    }
    window.location.reload();
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

