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
 * @copyright  Copyleft (c) 2012-2023
 * @version    3.0.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/main.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

// Headers anti-cache
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Inclui a classe FoursquareApi original
require_once __DIR__ . '/FoursquareAPI.Class.php';

use ElioTools\Security\SessionManager;
use ElioTools\Config\AppConfig;

$VERSAO = "3.0.0";

// Carrega o autoloader do Composer se disponível
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    
    $sessionManager = new SessionManager();
    $sessionManager->start();
    $oauth_token = $sessionManager->get('oauth_token') ?? $_SESSION['oauth_token'] ?? null;
    
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
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools</title>
<meta charset="utf-8">
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script src="js/main.js"></script>
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
$cache_file = "/tmp/cache-" . md5($_SERVER['REQUEST_URI']);
if (file_exists($cache_file) && (filemtime($cache_file) > (time() - 3600 * 12))) {
	// Cache file is less than 12 hours old. 
	// Don't bother refreshing, just use the file as-is.
	$response = file_get_contents($cache_file);
	$categories = json_decode($response);
	$categories_loaded = true;
} else {
	// Our cache is out-of-date, so load the data from our remote server.
	$response = carregarListaCategorias();
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
		setLocalCache("categorias", $response);
	} else {
		// JSON data is invalid so delete it and clear cache.
		$categories_loaded = false;
		if (file_exists($cache_file))
			unlink($cache_file);
		removeLocalCache("categorias");
	}
}

function carregarListaCategorias() {
	try {
		/*** Set client key and secret ***/
		$config = new AppConfig();

		/*** Load the Foursquare API library ***/
		$foursquare = new FoursquareApi($config->get('client_key'), $config->get('client_secret'));
		
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
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css?v=<?php echo time(); ?>">
<link rel="stylesheet" type="text/css" href="includes/session-status-bar-variants.css?v=<?php echo time(); ?>">
<script src="js/session-manager.js"></script>
</head>
<body class="tundra">

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
						<div class="urlinput"><input type="text" id="pagina" name="pagina" required="true" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 50.2em; margin-bottom: 3px"/></div>
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
		<div dojoType="dijit.layout.ContentPane" title="Informar IDs ou URLs dos locais">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_ids" jsId="f_ids" action="load.php" method="post">
				<section class="toolcontainer">
					<div class="row">
						<div class="urlstextarealabel"><label for="textarea_ids"><a id="dlg_ids" href="javascript:showDialogIds();">IDs ou URLs</a>:</label></div>
						<div class="urlstextarea"><textarea id="textarea_ids" name="textarea" dojoType="dijit.form.SimpleTextarea" maxLength="4000" trim="true" style="font-family: Arial, Helvetica, Verdana, sans-serif; font-size: 13px; resize: none; width: 647px; height: 59px;"></textarea></div>
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
		<div dojoType="dijit.layout.ContentPane" title="Pesquisar locais" selected="true">
			<div dojoType="dijit.form.Form" enctype="multipart/form-data" accodigopostalt-charset="utf-8" id="f_src" jsId="f_src" action="search.php" method="post">
				<input type="hidden" id="oauth_token_scr" name="oauth_token" value="<?= $oauth_token ?>"/>
				<section class="toolcontainer">
					<div class="row">
						<div class="queryinputlabel"><label for="query"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Consulta</a>:</label></div>
						<div class="queryinput"><input type="text" id="query" name="query" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 299px"></div>
						<div class="llinputlabel"><label for="ll"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Local</a>:</label></div>
						<div class="llinput"><input type="text" id="ll" name="ll" required="false" dojoType="dijit.form.ValidationTextBox" trim="true" style="width: 296px"/></div>
					</div>
					<div class="row">
						<div class="categoryidcomboboxlabel"><label for="categoryId"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Categoria</a>:</label></div>
						<div class="categoryidcombobox">
							<div class="combobox">
								<select data-dojo-id="categoryId" name="categoryId" id="categoryId" data-dojo-type="dijit/form/FilteringSelect">
<?php
	$options = '									<option value=""></option>
	';
	if ($categories_loaded) {
		$categories_names = array();
	
		foreach ($categories->response->categories as $category):
			if (property_exists($category, "categories"))
				foreach ($category->categories as $category2):
					if (property_exists($category2, "name")) {
						$categories_names[$category2->id] = $category2->name;
					}
					if (property_exists($category2, "categories"))
						foreach ($category2->categories as $category3):
							if (property_exists($category3, "name")) {
								$categories_names[$category3->id] = $category3->name;
							}
							if (property_exists($category3, "categories"))
								foreach ($category3->categories as $category4):
									if (property_exists($category4, "name")) {
										$categories_names[$category4->id] = $category4->name;
									}
								endforeach;
						endforeach;
				endforeach;
		endforeach;

		asort($categories_names);
		foreach ($categories_names as $key => $val) {
			$options .=
	'									<option value="' . $key . '">' . $val . '</option>
	';
		}
	}

	echo $options;
?>
								</select>
							</div>
						</div>

						<div class="radiuscomboboxlabel"><label for="radius"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Raio</a>:</label></div>
						<div class="radiuscombobox">
							<div class="comboboxes">
								<select data-dojo-id="radius" name="radius" id="radius" data-dojo-type="dijit/form/Select">
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
						<div class="intentcomboboxlabel"><label for="intent"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Inten&ccedil;&atilde;o</a>:</label></div>
						<div class="intentcombobox">
							<div class="comboboxes">
								<select data-dojo-id="intent" name="intent" id="intent" data-dojo-type="dijit/form/Select">
									<option value="checkin">checkin</option>
									<option value="browse">browse</option>
									<option value="global">global</option>
									<option value="match">match</option>
								</select>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="limitcomboboxlabel"><label for="limit"><a href="https://docs.foursquare.com/developer/reference/place-search" target="_blank">Limite</a>:</label></div>
						<div class="limitcombobox">
							<div class="comboboxes">
								<select data-dojo-id="limit" name="limit" id="limit" id="intent" style="margin-bottom: 3px" data-dojo-type="dijit/form/Select">
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
<!-- Start of StatCounter Code for Default Guide -->
<script type="text/javascript">
var sc_project=7288306;
var sc_invisible=1;
var sc_security="a38fdf67";
</script>
<script type="text/javascript"
src="https://www.statcounter.com/counter/counter.js"
async></script>
<noscript><div class="statcounter"><a title="Web Analytics"
href="http://statcounter.com/" target="_blank"><img
class="statcounter"
src="//c.statcounter.com/7288306/0/a38fdf67/1/" alt="Web
Analytics"></a></div></noscript>
<!-- End of Statcounter Code -->

<!-- End of StatCounter Code for Default Guide -->
</body>
</html>
