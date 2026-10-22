<?php

declare(strict_types=1);

/**
 * Main Page
 *
 * Página principal de acesso às ferramentas de importação e pesquisa
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    3.4.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/main.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

// Headers anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Analytics interno (privacy-friendly)
require_once __DIR__ . '/analytics.php';

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Inclui a classe FoursquareApi original
require_once __DIR__ . '/FoursquareAPI.Class.php';

use ElioTools\Security\SessionManager;
use ElioTools\Config\AppConfig;

// Lê versão dinamicamente do build-info.json (gerado a cada build/CI)
$_versionFile = __DIR__ . '/build-info.json';
$VERSAO = '3.4.0'; // fallback para ambiente sem build
if (file_exists($_versionFile)) {
    $_buildData = @json_decode(@file_get_contents($_versionFile), true);
    if (!empty($_buildData['version'])) {
		// Extrai apenas a parte SemVer (ex: "v3.4.0-1-g590b203" → "3.4.0")
        if (preg_match('/^v?(\d+\.\d+\.\d+)/', $_buildData['version'], $_m)) {
            $VERSAO = $_m[1];
        }
        unset($_m);
    }
    unset($_buildData);
}
unset($_versionFile);

$isPostLoginNavigation = false;

// Carrega o autoloader do Composer se disponível
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $sessionManager = new SessionManager();
    $sessionManager->start();
    
    // Debug: Log estado da sessão e cookies
    error_log("main.php: Session ID = " . session_id());
    error_log("main.php: Session oauth_token = " . ($sessionManager->get('oauth_token') ?? 'NULL'));
    error_log("main.php: Cookie oauth_token = " . ($_COOKIE['oauth_token'] ?? 'NULL'));
    
    // Busca token da sessão, cookie ou superglobal (ordem de prioridade)
    $oauth_token = $sessionManager->get('oauth_token') 
                ?? $_SESSION['oauth_token'] 
                ?? ($_COOKIE['oauth_token'] ?? null);
    
    error_log("main.php: Final oauth_token = " . ($oauth_token ?? 'NULL'));
    
    // Se encontrou o token no cookie mas não na sessão, salva na sessão
    if ($oauth_token && !$sessionManager->get('oauth_token')) {
        $sessionManager->set('oauth_token', $oauth_token);
        error_log("main.php: Token restaurado do cookie para sessão");
    }
    
    // Obtém dados do usuário da sessão ou busca da API
    $userData = $sessionManager->get('user_data') ?? $_SESSION['user_data'] ?? null;
    if (!$userData && $oauth_token) {
        try {
            $config = new AppConfig();
            $foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));
            $foursquare->SetAccessToken($oauth_token);
            $responseData = $foursquare->GetPrivate("users/self");
            $response = json_decode($responseData, true);
            
            if (isset($response['response']['user'])) {
                $userData = $response['response']['user'];
                $sessionManager->set('user_data', $userData);
                // Define o cookie para compatibilidade
                $firstName = $userData['firstName'] ?? '';
                $lastName = $userData['lastName'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);
                if ($fullName) {
                    $sessionManager->setCookie("name", rawurlencode($fullName), time() + 60*60*24);
                }
            }
        } catch (Exception $e) {
            // Em caso de erro, redireciona para login com flag de erro para evitar loop
            header('Location: index.php?error=auth_failed');
            exit;
        }
    }
} else {
    // Fallback para sistema legado
    if (!isset($_SESSION))
        session_start();
    $oauth_token = $_SESSION["oauth_token"] ?? null;
}

if (!$oauth_token) {
    header('Location: index.php');
    exit;
}

// No fluxo pós-login (index.php -> main.php), evita alerta de restart desnecessário.
$httpReferer = $_SERVER['HTTP_REFERER'] ?? '';
if (preg_match('~/index\.php(?:\?|$)~i', $httpReferer) === 1) {
	$isPostLoginNavigation = true;
}
?>
<?php include_once 'includes/asset_helper.php'; ?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools</title>
<meta charset="utf-8">
<script>
	window.__FMET_SUPPRESS_RESTART_WARNING_ONCE__ = <?php echo $isPostLoginNavigation ? 'true' : 'false'; ?>;
</script>
<?php dojo_script(['parseOnLoad' => true]); ?>
<?php script_versioned('js/session-manager.js'); ?>
<script src="js/google-maps-config.php?v=5.0.1" defer></script>
<?php script_versioned('js/google-maps.js', ['defer' => 'defer']); ?>
<?php script_versioned('js/fsq-autocomplete.js'); ?>
<?php script_versioned('js/main.js'); ?>
<script>
    // Remove o fragmento #_=_ adicionado por alguns provedores OAuth
    if (window.location.hash && window.location.hash === '#_=_') {
        if (window.history && window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href.split('#')[0]);
        } else {
            // Fallback para navegadores antigos
            window.location.hash = '';
        }
    }
</script>
<?php
$categoryLocale = obterLocaleCategorias();
$cache_file = "/tmp/cache-" . md5($_SERVER['REQUEST_URI'] . '|' . $categoryLocale);
if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 3600 * 12))) {
	// Cache file is less than 12 hours old. 
	// Don't bother refreshing, just use the file as-is.
	$response = file_get_contents($cache_file);
	$categories = json_decode($response);
	$categories_loaded = true;
} else {
	// Our cache is out-of-date, so load the data from our remote server.
	$response = carregarListaCategorias($categoryLocale);
	if (empty($response)) {
		$categories = new stdClass();
		$categories->meta = "";
	} else {
		$categories = json_decode($response);
	}
	if (property_exists($categories->meta, "code") && ($categories->meta->code == "200")) {
		// JSON data is valid so save it over our cache for next time.
		$categories_loaded = true;
		file_put_contents($cache_file, $response, LOCK_EX);
		setLocalCache("categorias_" . $categoryLocale, $response);
	} else {
		// JSON data is invalid so delete it and clear cache.
		$categories_loaded = false;
		if (file_exists($cache_file))
			unlink($cache_file);
		removeLocalCache("categorias_" . $categoryLocale);
	}
}

function obterLocaleCategorias(): string {
	$acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
	$localePreferido = 'pt';
	$qualidadePreferida = -1.0;

	foreach (explode(',', $acceptLanguage) as $idiomaAceito) {
		if (!preg_match('/^\s*([a-z]{2,3})(?:-[a-z0-9]{2,8})*(?:\s*;\s*q=([0-9.]+))?/i', $idiomaAceito, $correspondencia)) {
			continue;
		}

		$qualidade = isset($correspondencia[2]) ? (float) $correspondencia[2] : 1.0;
		if ($qualidade > $qualidadePreferida && $qualidade > 0) {
			$localePreferido = strtolower($correspondencia[1]);
			$qualidadePreferida = $qualidade;
		}
	}

	return $localePreferido;
}

function carregarListaCategorias(string $locale) {
	try {
		/*** Set client key and secret ***/
		$config = new AppConfig();

		/*** Load the Foursquare API library ***/
		$foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'), '', 'v2', $locale);
		
		// Obtém token da sessão
		$sessionManager = new SessionManager();
		$sessionManager->start();
		$token = $sessionManager->get('oauth_token');
		
		if (!$token) {
			throw new Exception('Token não encontrado');
		}
		
		$foursquare->SetAccessToken($token);
		
		$response = $foursquare->GetPrivate("venues/categories");
		return $response; // Já é JSON string
	} catch (Exception $e) {
		error_log("Erro ao carregar categorias: " . $e->getMessage());
		return false;
	}
}

function setLocalCache($key, $data) {
	print('<script>localStorage.setItem(\''.$key.'\', \''.str_replace("'", "\'", $data).'\');</script>');
	print str_pad('', intval(ini_get('output_buffering')));
	flush();
}

function removeLocalCache($key) {
	print('<script>if (localStorage && localStorage.getItem(\''.$key.'\')) localStorage.removeItem(\''.$key.'\');</script>');
	print str_pad('', intval(ini_get('output_buffering')));
	flush();
}
?>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<?php dojo_theme('tundra'); ?>
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" type="text/css" href="includes/session-status-bar-variants.css?v=<?php echo time(); ?>">
<style>
/* Loading Overlay - Inline para carregamento imediato */
#app-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 99999;
    opacity: 1;
    transition: opacity 0.5s ease-out;
}

#app-loading-overlay.fade-out {
    opacity: 0;
    pointer-events: none;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading-text {
    color: white;
    font-family: "Inter", -apple-system, BlinkMacSystemFont, Helvetica, Arial, sans-serif;
    font-size: 18px;
    font-weight: 500;
    margin-top: 20px;
    text-align: center;
}

.loading-subtext {
    color: rgba(255, 255, 255, 0.8);
    font-family: "Inter", -apple-system, BlinkMacSystemFont, Helvetica, Arial, sans-serif;
    font-size: 14px;
    margin-top: 8px;
}

/* Oculta o conteúdo principal até o Dojo terminar */
body.loading #intro,
body.loading #options,
body.loading #links {
    visibility: hidden;
}
</style>
</head>
<body class="tundra loading">

<!-- Loading Overlay -->
<div id="app-loading-overlay">
    <div class="loading-spinner"></div>
    <div class="loading-text">Carregando Elio Tools</div>
    <div class="loading-subtext">Preparando interface...</div>
</div>

<?php include 'includes/session-status-bar.php'; ?>

<header>
	<h2>Edi&ccedil;&atilde;o de locais em massa via API <span>(v<?php echo $VERSAO; ?>)</span></h2>
</header>
<article id="intro">
<?php
// Debug: verificar se há dados do usuário
if (!isset($_COOKIE['name']) || empty($_COOKIE['name'])) {
    // Tenta obter dados do usuário da sessão e criar o cookie
    if (isset($userData) && is_array($userData)) {
        $firstName = $userData['firstName'] ?? '';
        $lastName = $userData['lastName'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        if ($fullName) {
            if (isset($sessionManager)) {
                $sessionManager->setCookie("name", rawurlencode($fullName), time() + 60*60*24);
            } else {
                setcookie("name", rawurlencode($fullName), time() + 60*60*24, "/");
            }
            $_COOKIE['name'] = rawurlencode($fullName); // Define para uso imediato
        }
    }
}

if ((isset($_COOKIE['name'])) && (strlen($_COOKIE['name']) > 0))
	echo "<p>Ol&aacute;, " . rawurldecode($_COOKIE['name']) . "!</p>";
?>
	<p>Este aplicativo usa a API do Foursquare&reg;, mas n&atilde;o &eacute; endossado ou certificado pelo Foursquare Labs, Inc. Todos os logos do Foursquare&reg; e marcas registradas exibidas neste aplicativo s&atilde;o de propriedade do Foursquare Labs, Inc.</p>
	<p>Se desejar apoiar financeiramente este projeto, sua contribui&ccedil;&atilde;o ser&aacute; muito bem-vinda. <a type="button" href="https://github.com/sponsors/gavlinski" target="_blank" rel="noopener noreferrer" title="Contribua pelo GitHub Sponsors" aria-label="Apoie financeiramente este projeto pelo GitHub Sponsors"><svg data-component="Octicon" aria-hidden="true" focusable="false" class="octicon octicon-heart" viewBox="0 0 16 16" width="16" height="16" fill="currentColor" display="inline-block" overflow="visible" style="vertical-align: text-bottom;"><path d="m8 14.25.345.666a.75.75 0 0 1-.69 0l-.008-.004-.018-.01a7.152 7.152 0 0 1-.31-.17 22.055 22.055 0 0 1-3.434-2.414C2.045 10.731 0 8.35 0 5.5 0 2.836 2.086 1 4.25 1 5.797 1 7.153 1.802 8 3.02 8.847 1.802 10.203 1 11.75 1 13.914 1 16 2.836 16 5.5c0 2.85-2.045 5.231-3.885 6.818a22.066 22.066 0 0 1-3.744 2.584l-.018.01-.006.003h-.002ZM4.25 2.5c-1.336 0-2.75 1.164-2.75 3 0 2.15 1.58 4.144 3.365 5.682A20.58 20.58 0 0 0 8 13.393a20.58 20.58 0 0 0 3.135-2.211C12.92 9.644 14.5 7.65 14.5 5.5c0-1.836-1.414-3-2.75-3-1.373 0-2.609.986-3.029 2.456a.749.749 0 0 1-1.442 0C6.859 3.486 5.623 2.5 4.25 2.5Z"></path></svg></a>
</article>
<article id="options">
	<div id="accordion" dojoType="dijit.layout.AccordionContainer" doLayout="false">
		<div dojoType="dijit.layout.ContentPane" title="Importar dados de um arquivo CSV">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accept-charset="utf-8" id="f_csv" jsId="f_csv" action="load_csv.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="filelabel"><label for="uploader_csv"><a id="dlg_csv" href="javascript:showDialogCsv();">Arquivo</a>:</label></div>
						<div class="uploadbutton">
						  <input type="hidden" name="MAX_FILE_SIZE" value="500000" dojoType="dijit.form.TextBox">
						  <input name="csv" multiple="false" type="file" data-dojo-type="dojox.form.Uploader" label="Escolher arquivo" id="uploader_csv">
						</div>
						<div class="selectedfile" id="arquivo_csv">Nenhum arquivo selecionado</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Importar lista de um arquivo de texto simples">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accept-charset="utf-8" id="f_txt" jsId="f_txt" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="filelabel"><label for="txt"><a id="dlg_txt" href="javascript:showDialogTxt();">Arquivo</a>:</label></div>
						<div class="uploadbutton">
							<input type="hidden" name="MAX_FILE_SIZE" value="5000000" dojoType="dijit.form.TextBox">
							<input name="txt" multiple="false" type="file" data-dojo-type="dojox.form.Uploader" label="Escolher arquivo" id="uploader_txt">
							</div>
						<div class="selectedfile" id="arquivo_txt">Nenhum arquivo selecionado</div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos1">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome1" name="campos1[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome1">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco1" name="campos1[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco1">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal1" name="campos1[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal1">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro1" name="campos1[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro1">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade1" name="campos1[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade1">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado1" name="campos1[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado1">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal1" name="campos1[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal1">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro1" name="campos1[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro1">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone1" name="campos1[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone1">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb1" name="campos1[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb1">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter1" name="campos1[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter1">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook1" name="campos1[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook1">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram1" name="campos1[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram1">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng1" name="campos1[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng1">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao1" name="campos1[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao1">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu1" name="campos1[]" dojoType="dijit.form.CheckBox" value="menu">
								<label for="menu1">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="redes1" name="campos1[]" dojoType="dijit.form.CheckBox" value="redes">
								<label for="redes1">Redes</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas1" name="campos1[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas1">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_txt.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Importar lista de uma p&aacute;gina web">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_lks" jsId="f_lks" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="urlinputlabel"><label for="pagina"><a id="dlg_lks" href="javascript:showDialogLks();">Endere&ccedil;o</a>:</label></div>
						<div class="urlinput"><input type="text" id="pagina" name="pagina" required="true" dojoType="dijit.form.ValidationTextBox" trim="true" placeholder="Link da p&aacute;gina com os IDs dos locais" style="width: 658px; margin-bottom: 3px"/></div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos2">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome2" name="campos2[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome2">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco2" name="campos2[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco2">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal2" name="campos2[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal2">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro2" name="campos2[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro2">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade2" name="campos2[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade2">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado2" name="campos2[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado2">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal2" name="campos2[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal2">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro2" name="campos2[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro2">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone2" name="campos2[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone2">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb2" name="campos2[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb2">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter2" name="campos2[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter2">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook2" name="campos2[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook2">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram2" name="campos2[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram2">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng2" name="campos2[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng2">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao2" name="campos2[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao2">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu2" name="campos2[]" dojoType="dijit.form.CheckBox" value="menu">
								<label for="menu2">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="redes2" name="campos2[]" dojoType="dijit.form.CheckBox" value="redes">
								<label for="redes2">Redes</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas2" name="campos2[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas2">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_lks.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div id="paneDentro" dojoType="dijit.layout.ContentPane" title="Importar locais dentro de outro local" style="height: 174px;">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accept-charset="utf-8" id="f_dentro" jsId="f_dentro" action="children.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="queryinputlabel"><label for="parentSearchMain"><a id="dlg_dentro" href="javascript:showDialogDentro();">Dentro</a>:</label></div>
						<div class="queryinput" style="position: relative; padding-bottom: 8px;">
							<input type="text" id="parentSearchMain" dojoType="dijit.form.TextBox" autocomplete="off" placeHolder="Nome, ID ou link do local pai" style="width: 302px; box-sizing: border-box;">
							<input type="hidden" id="parentIdMain" name="parentId" value="">
							<div id="parentSearchMainResults" class="chain-search-results visual-results-list" style="display: none; position: absolute; z-index: 1000; width: 340px; max-height: 260px; overflow-y: auto; background: #fff; border: 1px solid #ccc;"></div>
							<div id="parentSearchMainStatus" style="display: none; font-size: 11px; color: #888;"></div>
						</div>
						<div class="llinputlabel"><label for="ll5"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Regi&atilde;o</a>:</label></div>
						<div class="llinput"><input type="text" id="ll5" name="ll" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" placeHolder="Lat,Lng ou Cidade" style="width: 286px"/></div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos5">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome5" name="campos5[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome5">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco5" name="campos5[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco5">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal5" name="campos5[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal5">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro5" name="campos5[]" dojoType="dijit.form.CheckBox" value="bairro" disabled>
								<label for="bairro5">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade5" name="campos5[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade5">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado5" name="campos5[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado5">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal5" name="campos5[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal5">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro5" name="campos5[]" dojoType="dijit.form.CheckBox" value="dentro" disabled>
								<label for="dentro5">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone5" name="campos5[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone5">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb5" name="campos5[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb5">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter5" name="campos5[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter5">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook5" name="campos5[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook5">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram5" name="campos5[]" dojoType="dijit.form.CheckBox" value="instagram" disabled>
								<label for="instagram5">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng5" name="campos5[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng5">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao5" name="campos5[]" dojoType="dijit.form.CheckBox" value="descricao" disabled>
								<label for="descricao5">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu5" name="campos5[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu5">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="redes5" name="campos5[]" dojoType="dijit.form.CheckBox" value="redes">
								<label for="redes5">Redes</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas5" name="campos5[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas5">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<div>
					<button dojoType="dijit.form.Button" type="submit" class="continue">
						Continuar
					</button>
				</div>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Informar IDs ou URLs dos locais">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_ids" jsId="f_ids" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="urlstextarealabel"><label for="textarea_ids"><a id="dlg_ids" href="javascript:showDialogIds();">IDs ou URLs</a>:</label></div>
						<div class="urlstextarea"><textarea id="textarea_ids" name="textarea" dojoType="dijit.form.SimpleTextarea" maxLength="4000" trim="true" style="font-family: 'Courier New', monospace; font-size: 13px; resize: none; width: 653px; height: 59px;"></textarea></div>
					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos3">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome3" name="campos3[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome3">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco3" name="campos3[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco3">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal3" name="campos3[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal3">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro3" name="campos3[]" dojoType="dijit.form.CheckBox" value="bairro">
								<label for="bairro3">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade3" name="campos3[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade3">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado3" name="campos3[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado3">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal3" name="campos3[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal3">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro3" name="campos3[]" dojoType="dijit.form.CheckBox" value="dentro">
								<label for="dentro3">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone3" name="campos3[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone3">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb3" name="campos3[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb3">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter3" name="campos3[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter3">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook3" name="campos3[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook3">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram3" name="campos3[]" dojoType="dijit.form.CheckBox" value="instagram">
								<label for="instagram3">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng3" name="campos3[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng3">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao3" name="campos3[]" dojoType="dijit.form.CheckBox" value="descricao">
								<label for="descricao3">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu3" name="campos3[]" dojoType="dijit.form.CheckBox" value="menu">
								<label for="menu3">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="redes3" name="campos3[]" dojoType="dijit.form.CheckBox" value="redes">
								<label for="redes3">Redes</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas3" name="campos3[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas3">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
				<button dojoType="dijit.form.Button" type=button onClick="console.log(f_ids.getValues())">
					Get Values from form!
				</button>
				-->
				<button dojoType="dijit.form.Button" type="submit" class="continue">
					Continuar
				</button>
			</div>
		</div>
		<div dojoType="dijit.layout.ContentPane" title="Pesquisar locais" selected="true" style="height: 206px;">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_src" jsId="f_src" action="search.php" method="post">
				<input type="hidden" id="oauth_token_scr" name="oauth_token" value="<?= $oauth_token ?>"/>
				<section class="toolcontainer">
					<div class="row">
						<div class="queryinputlabel"><label for="query"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Consulta</a>:</label></div>
						<div class="queryinput"><input type="text" id="query" name="query" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 220px"/></div>
						<div class="llinputlabel"><label for="ll"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Regi&atilde;o</a>:</label></div>
						<div class="llinput"><input type="text" id="ll" name="ll" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" placeHolder="Lat,Lng ou Cidade" style="width: 239px"/></div>
						<div class="radiuscomboboxlabel"><label for="radius"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Raio</a>:</label></div>
						<div class="radiuscombobox">
							<div class="comboboxes">
								<select data-dojo-id="radius" name="radius" id="radius" style="width: 70px;" data-dojo-type="dijit/form/Select">
									<option value="">Padr&atilde;o</option>
									<option value="50">50 m</option>
									<option value="100">100 m</option>
									<option value="250">250 m</option>
									<option value="500">500 m</option>
									<option value="1000">1 km</option>
									<option value="5000">5 km</option>
									<option value="10000">10 km</option>
									<option value="50000">50 km</option>
									<!--<option value="100000">100 km</option>-->
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="categoryidcomboboxlabel"><label for="categorySearchMain"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Categoria</a>:</label></div>
						<div class="categoryidcombobox">
							<div class="combobox" style="position: relative;">
								<input type="text" id="categorySearchMain" dojoType="dijit.form.TextBox" autocomplete="off" placeHolder="Digite para buscar uma categoria..." style="width: 368px; box-sizing: border-box;">
								<input type="hidden" id="categoryId" name="categoryId" value="">
								<div id="categorySearchMainResults" class="chain-search-results visual-results-list" style="display: none; position: absolute; z-index: 1000; width: 350px; max-height: 266px; overflow-y: auto; background: #fff; border: 1px solid #ccc;"></div>
								<div id="categorySearchMainStatus" style="font-size: 11px; color: #888;"></div>
							</div>
						</div>

						<div class="intentcomboboxlabel"><label for="intent"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Inten&ccedil;&atilde;o</a>:</label></div>
						<div class="intentcombobox">
							<div class="comboboxes">
								<select data-dojo-id="intent" name="intent" id="intent" style="width: 82px;" data-dojo-type="dijit/form/Select">
									<option value="checkin">checkin</option>
									<option value="browse">browse</option>
									<option value="global">global</option>
									<option value="match">match</option>
								</select>
							</div>
						</div>
						<div class="limitcomboboxlabel"><label for="limit"><a href="https://docs.foursquare.com/fsq-developers-places/reference/place-search" target="_blank">Limite</a>:</label></div>
						<div class="limitcombobox">
							<div class="comboboxes">
								<select data-dojo-id="limit" name="limit" id="limit" style="width: 58px; margin-bottom: 3px;" data-dojo-type="dijit/form/Select">
									<option value="10">10</option>
									<option value="25">25</option>
									<option value="50" selected="selected">50</option>
									<option value="100">100</option>
									<option value="150">150</option>
									<option value="200">200</option>
									<option value="250">250</option>
								</select>
							</div>
						</div>

					</div>
					<div class="row">
						<div class="fieldslabel"><label for="campos4">Campos:</label></div>
						<div class="checkboxes">
							<div class="checkbox" style="width: 6.8em;">
								<input id="nome4" name="campos4[]" dojoType="dijit.form.CheckBox" value="nome">
								<label for="nome4">Nome</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="endereco4" name="campos4[]" dojoType="dijit.form.CheckBox" value="endereco">
								<label for="endereco4">Endere&ccedil;o</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="ruatransversal4" name="campos4[]" dojoType="dijit.form.CheckBox" value="ruatransversal">
								<label for="ruatransversal4">Rua transversal</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="bairro4" name="campos4[]" dojoType="dijit.form.CheckBox" value="bairro" disabled>
								<label for="bairro4">Bairro</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="cidade4" name="campos4[]" dojoType="dijit.form.CheckBox" value="cidade">
								<label for="cidade4">Cidade</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="estado4" name="campos4[]" dojoType="dijit.form.CheckBox" value="estado">
								<label for="estado4">Estado</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="codigopostal4" name="campos4[]" dojoType="dijit.form.CheckBox" value="codigopostal">
								<label for="codigopostal4">C&oacute;digo postal</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="dentro4" name="campos4[]" dojoType="dijit.form.CheckBox" value="dentro" disabled>
								<label for="dentro4">Dentro</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="telefone4" name="campos4[]" dojoType="dijit.form.CheckBox" value="telefone">
								<label for="telefone4">Telefone</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="sitedaweb4" name="campos4[]" dojoType="dijit.form.CheckBox" value="sitedaweb">
								<label for="sitedaweb4">Site da web</label>
							</div>
							<div class="checkbox" style="width: 5.5em;">
								<input id="twitter4" name="campos4[]" dojoType="dijit.form.CheckBox" value="twitter">
								<label for="twitter4">Twitter</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="facebook4" name="campos4[]" dojoType="dijit.form.CheckBox" value="facebook">
								<label for="facebook4">Facebook</label>
							</div>
							<div class="checkbox" style="width: 7em;">
								<input id="instagram4" name="campos4[]" dojoType="dijit.form.CheckBox" value="instagram" disabled>
								<label for="instagram4">Instagram</label>
							</div>
							<div class="checkbox" style="width: 7.5em;">
								<input id="latlng4" name="campos4[]" dojoType="dijit.form.CheckBox" value="latlng">
								<label for="latlng4">Lat/Lng</label>
							</div>
							<br>
							<div class="checkbox" style="width: 6.8em;">
								<input id="descricao4" name="campos4[]" dojoType="dijit.form.CheckBox" value="descricao" disabled>
								<label for="descricao4">Descri&ccedil;&atilde;o</label>
							</div>
							<div class="checkbox" style="width: 6.8em;">
								<input id="menu4" name="campos4[]" dojoType="dijit.form.CheckBox" value="menu" disabled>
								<label for="menu4">Menu</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="redes4" name="campos4[]" dojoType="dijit.form.CheckBox" value="redes">
								<label for="redes4">Redes</label>
							</div>
							<div class="checkbox" style="width: 9.5em;">
								<input id="horas4" name="campos4[]" dojoType="dijit.form.CheckBox" value="horas" disabled>
								<label for="horas4">Horas</label>
							</div>
						</div>
					</div>
				</section>
				<!--
					https://api.foursquare.com/v2/multi?v=20120321&requests=
					%2Fvenues%2Fsearch%3Fll%3D-16.01670379538501%252C-48.06514263153076,
					%2Fvenues%2Fsearch%3Fll%3D-16.11670379538501%252C-48.06514263153076 -->
				<div>
					<button dojoType="dijit.form.Button" type="submit" class="continue">
						Continuar
					</button>
					<!--<button dojoType="dijit.form.Button" type=button onClick="console.log(f_src.getValues())">
						Get Values from form!
					</button>-->
				</div>
			</div>
		</div>
	</div>
</article>
<footer id="links">
	<div id="fixedlinks">
		<div class="social">
			<a href="https://github.com" style="margin-right: 3px"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27s1.36.09 2 .27c1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8"/></svg></a> <a href="https://github.com/gavlinski">gavlinski</a> / <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools">Foursquare-Mass-Editor-Tools</a> / <a href="https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/releases">releases</a>
		</div>
		<div class="social">
			<a href="https://discord.com/invite/fsqplacemakers" style="margin-right: 3px"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#5865F2" viewBox="0 0 16 16"><path d="M13.545 2.907a13.2 13.2 0 0 0-3.257-1.011.05.05 0 0 0-.052.025c-.141.25-.297.577-.406.833a12.2 12.2 0 0 0-3.658 0 8 8 0 0 0-.412-.833.05.05 0 0 0-.052-.025c-1.125.194-2.22.534-3.257 1.011a.04.04 0 0 0-.021.018C.356 6.024-.213 9.047.066 12.032q.003.022.021.037a13.3 13.3 0 0 0 3.995 2.02.05.05 0 0 0 .056-.019q.463-.63.818-1.329a.05.05 0 0 0-.01-.059l-.018-.011a9 9 0 0 1-1.248-.595.05.05 0 0 1-.02-.066l.015-.019q.127-.095.248-.195a.05.05 0 0 1 .051-.007c2.619 1.196 5.454 1.196 8.041 0a.05.05 0 0 1 .053.007q.121.1.248.195a.05.05 0 0 1-.004.085 8 8 0 0 1-1.249.594.05.05 0 0 0-.03.03.05.05 0 0 0 .003.041c.24.465.515.909.817 1.329a.05.05 0 0 0 .056.019 13.2 13.2 0 0 0 4.001-2.02.05.05 0 0 0 .021-.037c.334-3.451-.559-6.449-2.366-9.106a.03.03 0 0 0-.02-.019m-8.198 7.307c-.789 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.45.73 1.438 1.613 0 .888-.637 1.612-1.438 1.612m5.316 0c-.788 0-1.438-.724-1.438-1.612s.637-1.613 1.438-1.613c.807 0 1.451.73 1.438 1.613 0 .888-.631 1.612-1.438 1.612"/></svg><a href="https://discord.com/invite/fsqplacemakers" style="margin-top: 1px">Foursquare Placemarkers</a>
		</div>
		<div class="social">
			<a href="https://x.com/gavlinski" style="margin-right: 3px"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 1227" fill="#000000" aria-hidden="true" width="12" height="16"><path d="M714.163 519.284 1160.89 0h-105.86L667.137 450.887 357.328 0H0l468.492 681.821L0 1226.37h105.866l409.625-476.152 327.181 476.152H1200L714.137 519.284h.026ZM569.165 687.828l-47.468-67.894-377.686-540.24h162.604l304.797 435.991 47.468 67.894 396.2 566.721H892.476L569.165 687.854v-.026Z"></path></svg><a href="https://x.com/gavlinski" style="margin-top: 0.5px">@gavlinski</a>
		</div>
	</div>
</footer>

<script>
// Gerenciamento do Loading Overlay
(function() {
    'use strict';
    
    var overlayRemoved = false;
    var loadingStartTime = Date.now();
    
    function removeLoadingOverlay() {
        if (overlayRemoved) return;
        overlayRemoved = true;
        
        var loadTime = Date.now() - loadingStartTime;
        console.log('✅ Dojo carregado em ' + loadTime + 'ms');
        
        var overlay = document.getElementById('app-loading-overlay');
        var body = document.body;
        
        if (overlay) {
            // Fade out suave
            overlay.classList.add('fade-out');
            
            // Remove após animação
            setTimeout(function() {
                if (overlay.parentNode) {
                    overlay.parentNode.removeChild(overlay);
                }
                body.classList.remove('loading');
                console.log('🎨 Interface pronta');
            }, 500);
        } else {
            body.classList.remove('loading');
        }
    }
    
    // Método 1: Dojo ready (preferencial)
    function waitForDojo() {
        if (typeof dojo !== 'undefined' && dojo.ready) {
            dojo.ready(function() {
                // Aguarda parsing completo dos widgets
                setTimeout(removeLoadingOverlay, 100);
            });
        } else if (typeof require !== 'undefined') {
            require(['dojo/ready', 'dojo/parser'], function(ready, parser) {
                ready(function() {
                    setTimeout(removeLoadingOverlay, 100);
                });
            });
        } else {
            // Retry se Dojo ainda não carregou
            setTimeout(waitForDojo, 50);
        }
    }
    
    // Método 2: Fallback com timeout (segurança)
    var fallbackTimeout = setTimeout(function() {
        console.warn('⚠️ Timeout: Removendo overlay após 30 segundos');
        removeLoadingOverlay();
    }, 30000);
    
    // Método 3: window.onload como backup adicional
    window.addEventListener('load', function() {
        setTimeout(function() {
            if (!overlayRemoved) {
                console.log('📦 window.onload: Removendo overlay');
                removeLoadingOverlay();
            }
        }, 500);
    });
    
    // Inicia detecção do Dojo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForDojo);
    } else {
        waitForDojo();
    }
})();
</script>

</body>
</html>
