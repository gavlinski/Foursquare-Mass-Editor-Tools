<?php

/**
 * Search Venues
 *
 * Pesquisa venues utilizando a API https://api.foursquare.com/v2/venues/search
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2012-2026
 * @version    3.0.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/search.php
 * @since      File available since Release 1.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt
 */

if (!isset($_SESSION))
	session_start();
if (!isset($_SESSION["oauth_token"])) {
	header('Location: index.php');
}

// Inclui helper de assets
require_once __DIR__ . '/includes/asset_helper.php';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Pesquisando...</title>
<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<?php
$DOJO_THEME_URL = dojo_theme_url('tundra');
define("VERSION", "Venues Searcher 3.0.0");
define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="' . $DOJO_THEME_URL . '">
<link rel="stylesheet" type="text/css" href="estilo.css">
');
ob_start();
dojo_script(["parseOnLoad" => true]);
define("DOJO_INIT", ob_get_clean());
define("HBODY", '</head>
<body class="tundra">
');
define("PESQUISANDO", LINKS . DOJO_INIT . HBODY . '<div id="carregando">Pesquisando venues&hellip;</div>
');
define("TEMPLATE1", '<script>dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", LINKS . DOJO_INIT . HBODY . TEMPLATE1 . '<p>Erro na convers&atilde;o do endere&ccedil;o em coordenadas geogr&aacute;ficas.</p>
<p>Verifique o endere&ccedil;o ou as coordenadas e tente novamente.</p>
' . TEMPLATE2);
define("ERRO02", LINKS . DOJO_INIT . HBODY . TEMPLATE1 . '<p>Nenhuma venue encontrada nas coordenadas geogr&aacute;ficas informadas.</p>
<p>Verifique a latitude e longitude e tente novamente.</p>
' . TEMPLATE2);
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.php">
' . LINKS . DOJO_INIT . '</head>
<body>
<p>Erro ao fazer a pesquisa.</p>
</body>
</html>');
define("EDIT", '<script>
	window.location = "edit.php"
</script>;');

if (isset($_POST["ll"]) && trim($_POST["ll"]) !== "")
	$params["ll"] = stripAccents($_POST["ll"]);
else {
	// Se ll não foi fornecido, exibe erro
	echo ERRO99;
	exit;
}
if (isset($_POST["categoryId"]))
	$params["categoryId"] = $_POST["categoryId"];
if ((isset($_POST["query"])) && ($_POST["query"] != ""))
	$params["query"] = $_POST["query"];
if (isset($_POST["limit"]))
	$params["limit"] = $_POST["limit"];
if (isset($_POST["intent"]))
	$params["intent"] = $_POST["intent"];
if (isset($_POST["radius"]))
	$params["radius"] = $_POST["radius"];

if (isset($params))
	$data = pesquisarVenues($params);
else
	echo ERRO99;

$_SESSION["file"] = filtrarArray($file);
if ($_SESSION["file"] == null) {
	$pbar->hide();
	echo ERRO02;
	exit;
}
$_SESSION["venuesIds"] = filtrarArray($venuesIds);
$_SESSION["campos"] = $_POST["campos4"];
setLocalCache("txt", implode('%0A,', $_SESSION["file"]));
setLocalCache("venues", addslashes($data));
echo EDIT;

/**
 * Remove acentos de uma string
 * 
 * Converte caracteres acentuados para seus equivalentes ASCII.
 * Útil para normalização de endereços e queries de busca.
 * 
 * @param string $str String com acentos
 * @return string String sem acentos
 */
function stripAccents($str) {
    return strtr($str, utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}

/**
 * Filtra array removendo duplicatas
 * 
 * @param array $array Array a ser filtrado
 * @return array Array sem duplicatas e com índices reordenados
 */
function filtrarArray($array) {
	return array_values(array_unique($array));
}

/**
 * Pesquisa venues usando a API do Foursquare
 * 
 * Suporta pesquisas simples (até 50 resultados) e múltiplas (até 250 resultados)
 * usando diferentes coordenadas geográficas.
 * 
 * @param array $params Parâmetros da busca:
 *                      - ll: latitude,longitude ou endereço
 *                      - categoryId: ID da categoria (opcional)
 *                      - query: termo de busca (opcional)
 *                      - limit: número de resultados (1-250)
 *                      - intent: tipo de busca (checkin, browse, etc)
 *                      - radius: raio em metros (opcional)
 * @return string JSON com os resultados da busca
 */
function pesquisarVenues($params) {
	global $venuesIds;
	$venuesIds = array();
	global $file;
	$file = array();
		
	require_once("ProgressBar.Class.php");

	echo PESQUISANDO;
	$pbar = new ProgressBar();
	echo '<div style="width: 400px;">' . "\r\n";
	$pbar->render();
	echo '</div>' . "\r\n";

	require_once("FoursquareAPI.Class.php");

	/*** Set client key and secret ***/
	include 'includes/app_credentials.php';

	/*** Load the Foursquare API library ***/
	$foursquare = new FoursquareAPI($client_key, $client_secret);
	$foursquare -> SetAccessToken($_SESSION["oauth_token"]);
	
	/*** Leverages the Google Maps API to generate a lat/lng pair for a given address ***/
	// Se ll estiver vazio ou não for coordenadas válidas, tenta geocodificar
	if (!empty($params["ll"]) && (!preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $params["ll"]))) {
		$coordinates = $foursquare -> GeoLocate($params["ll"]);
		// Verifica se geocodificação foi bem-sucedida antes de acessar array
		if ($coordinates == null || !isset($coordinates["latitude"]) || !isset($coordinates["longitude"])) {
			$pbar->hide();
			echo ERRO01;
			exit;
		}
		$params["ll"] = $coordinates["latitude"] . "," . $coordinates["longitude"];
	}
	
	/*** Perform a request to a authenticated-only resource ***/
	if ($params["limit"] <= 50) {
		$response = $foursquare -> GetPrivate("venues/search", $params);
		$json = json_decode($response);
		
	/*** Perform multiple requests at once to a authenticated-only resource ***/
	} else {
		$limit = $params["limit"];
		$params["limit"] = 50;
		$requests[] = array("endpoint" => "venues/search") + $params;
		if ($limit >= 100) {
			$params["ll"] = $coordinates["southwest"];
			$requests[] = array("endpoint" => "venues/search") + $params;
			if ($limit >= 150) {
				$params["ll"] = $coordinates["northeast"];
				$requests[] = array("endpoint" => "venues/search") + $params;
				if ($limit >= 200) {
					$params["ll"] = $coordinates["southeast"];
					$requests[] = array("endpoint" => "venues/search") + $params;
					if ($limit == 250) {
						$params["ll"] = $coordinates["northwest"];
						$requests[] = array("endpoint" => "venues/search") + $params;
					}
				}
			}
		}
		$responses = $foursquare -> GetMulti($requests);
		$json = json_decode($responses);
	}
		
	/**
	 * Extrai IDs e URLs das venues do response JSON da API
	 * 
	 * @param array $json_response_venues Array de venues do response da API
	 * @param ProgressBar $pbar Instância da barra de progresso
	 * @param int $size Tamanho total para cálculo de progresso
	 * @param int $i Índice inicial
	 * @return array Array com chaves 'venues' (IDs) e 'file' (URLs)
	 */
	function extrairVenuesIdsUrls($json_response_venues, $pbar, $size, $i) {
		$array = array();
		$s = count($json_response_venues);
		if ($size < 50)
			$delta = 1;
		else
			$delta = ($size/($size/50))/$s;
		//echo("\$size = $size, \$s = $s, \$delta = $delta<br>");
		foreach ($json_response_venues as $venue) {
			if (property_exists($venue, "id")) {
				$array["venues"][] = $venue->id;
				// Usa canonicalUrl se disponível (formato: https://app.foursquare.com/v/name/id)
				if (property_exists($venue, "canonicalUrl"))
					$array["file"][] = $venue->canonicalUrl;
				else
					$array["file"][] = "https://app.foursquare.com/v/" . $venue->id;
			}
			$i += $delta;
			//echo("\$i = $i,");
			$pbar->setProgressBarProgress($i*100/$size);
			usleep(50000*0.1);
		}
		return $array;
	}
	
	if ((isset($json->meta->code)) && ($json->meta->code == 200)) {
	
		/*** Single request ***/
		if (isset($json->response->venues)) {
			$size = count($json->response->venues);
			if ($size > 0) {
				$array = extrairVenuesIdsUrls($json->response->venues, $pbar, $size, 0);
				$venuesIds = $array["venues"];
				$file = $array["file"];
			} else {
				$pbar->hide();
				echo ERRO02;
				exit;
			}
			
		/*** Multi requests ***/
		} else if (isset($json->response->responses)) {
			$size = count($json->response->responses)*50;
			$response = json_decode($responses, true);
			$i = 0;
			$response_venues = array();
			foreach ($json->response->responses as $resp) {
				if (count($resp->response->venues) > 0) {
					$array = extrairVenuesIdsUrls($resp->response->venues, $pbar, $size, $i);
					$r = $response["response"]["responses"][$i/50]["response"]["venues"];
					if (count($venuesIds) > 0) {
						foreach ($r as $key => &$value)
							if (!in_array($value["id"], $venuesIds))
								$response_venues["response"]["venues"][] = $r[$key];
							else
								unset($r[$key]); // remove venue duplicada
						unset($value);
					} else {
						$response_venues["response"]["venues"] = $r;
					}
					$venuesIds = array_merge($venuesIds, $array["venues"]);
					$file = array_merge($file, $array["file"]);
					$i += 50;
				} else {
					$i += 50;
					$pbar->setProgressBarProgress($i*100/$size);
					usleep(50000*0.1);
				}
			}
			if (!isset($response_venues["response"]["venues"])) {
				$pbar->hide();
				echo ERRO02;
				exit;
			}

			unset($response["response"]);
			$response = json_encode(array_merge($response, $response_venues));
		}

	} else if (isset($json->meta->code)) {
		$pbar->hide();
		echo TEMPLATE1 . '<p><b>Erro ' . $json->meta->code . ':</b> ' . $json->meta->errorType . '</p>
<p><b>Detalhe:</b> ' . $json->meta->errorDetail . '</p>
' . TEMPLATE2;
		exit;
	} else {
		$pbar->hide();
		echo TEMPLATE1 . '<p>Ocorreu um erro desconhecido. Por favor, tente novamente. Caso o problema persista, reinicie o seu navegador.</p>
' . TEMPLATE2;
		exit;
	}

	return $response;
}

/**
 * Define cache local no localStorage do navegador
 * 
 * @param string $key Nome da chave no localStorage
 * @param string $data Dados a serem armazenados
 * @return void
 */
function setLocalCache($key, $data) {
	print('<script>'."\n\r".'	localStorage.setItem(\''.$key.'\', \''.$data.'\');'."\n\r".'</script>');
	print str_pad('', intval(ini_get('output_buffering'))) . "\n\r";
	flush();
}

?>