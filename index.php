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

use ElioTools\Config\AppConfig;
use ElioTools\Security\SessionManager;
use ElioTools\Api\FoursquareApi;

// Inicializa configurações
$config = new AppConfig();
$sessionManager = new SessionManager();
$sessionManager->start();

// Inicializa API do Foursquare
$foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));

// Validação e obtenção do token
$token = null;
if (isset($_COOKIE['oauth_token']) && $_COOKIE['oauth_token'] !== "0") {
    $token = filter_var($_COOKIE['oauth_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
} elseif (isset($_GET['code'])) {
    $code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($code) {
        try {
            $token = $foursquare->getToken($code, $config->get('redirect_uri'));
        } catch (Exception $e) {
            error_log("Erro ao obter token: " . $e->getMessage());
            header('Location: error.php');
            exit;
        }
    }
}

// Validação e obtenção do token
$token = null;
if (isset($_COOKIE['oauth_token']) && $_COOKIE['oauth_token'] !== "0") {
    $token = filter_var($_COOKIE['oauth_token'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
} elseif (isset($_GET['code'])) {
    $code = filter_var($_GET['code'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    if ($code) {
        try {
            $token = $foursquare->getToken($code, $config->get('redirect_uri'));
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
    $foursquare->setAccessToken($token);

    try {
        // Perform a request to a authenticated-only resource
        $userData = $foursquare->getPrivate("users/self");
        
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
        header('Location: /4sqmet/load.php');
        exit;
    } else {
        header('Location: /4sqmet/main.php');
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
<link rel="stylesheet" type="text/css" href="estilo.css">
</head>
<body class="tundra">
<p>
	<?php

		echo "<a href='" . $foursquare->getAuthenticationUrl($config->get('redirect_uri')) . "'><img src='img/connectTo@2x-f07c1cb7c6ed8894bb14dedd1001bcf3.png' alt='Connect to this app via Foursquare'></a>";

	?>
</p>
</body>
</html>
<?php


?>

