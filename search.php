<?php

/**
 * Search Venues
 *
 * Pesquisa venues utilizando a API https://api.foursquare.com/v2/venues/search
 *
 * @category	 Foursquare
 * @package		 Foursquare-Mass-Editor-Tools
 * @author		 Elio Gavlinski <gavlinski@gmail.com>
 * @copyright	 Copyleft (c) 2012
 * @version		 2.0.0
 * @link			 https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/search.php
 * @since			 File available since Release 1.5
 * @license		 GPLv3 <http://www.gnu.org/licenses/gpl.txt
 */

if (!isset($_SESSION))
	session_start();
if (!isset($_SESSION["oauth_token"])) {
	header('Location: index.php');
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Pesquisando...</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<meta http-equiv="cache-control" content="no-cache"/>
<meta http-equiv="pragma" content="no-cache">
<?php
define("VERSION", "Venues Searcher 1.0");
define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
');
define("HBODY", '</head>
<body class="tundra">
');
define("PESQUISANDO", LINKS . HBODY . '<div id="carregando">Pesquisando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", TEMPLATE1 . '<p>Erro na convers&atilde;o do endere&ccedil;o em coordenadas geogr&aacute;ficas.</p>
<p>Verifique o endere&ccedil;o ou as coordenadas e tente novamente.</p>
' . TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>Nenhuma venue encontrada nas coordenadas geogr&aacute;ficas informadas.</p>
<p>Verifique a latitude e longitude e tente novamente.</p>
' . TEMPLATE2);
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.php">
' . LINKS . '</head>
<body>
<p>Erro ao fazer a pesquisa.</p>
</body>
</html>');
define("EDIT", '<script type="text/javascript">
	window.location = "edit.php"
</script>;');

if (isset($_POST["ll"]))
	$params["ll"] = stripAccents($_POST["ll"]);
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
$_SESSION["venues"] = filtrarArray($venues);
$_SESSION["campos"] = $_POST["campos4"];
setLocalCache("txt", implode('%0A,', $_SESSION["file"]));
setLocalCache("venues", addslashes($data));
echo EDIT;

function stripAccents($str) {
    return strtr($str, utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
}

function filtrarArray($array) {
	return array_values(array_unique($array));
}

function pesquisarVenues($params) {
	global $venues;
	$venues = array();
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
	if ((!preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $params["ll"])) ||  ($params["limit"] > 50)) {
		$coordinates = $foursquare -> GeoLocate($params["ll"]);
		$params["ll"] = $coordinates["latitude"] . "," . $coordinates["longitude"];
		if ($coordinates == null) {
			$pbar->hide();
			echo ERRO01;
			exit;
		}
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
				if (property_exists($venue, "canonicalUrl"))
					$array["file"][] = $venue->canonicalUrl;
				else
					$array["file"][] = "https://foursquare.com/v/" . $venue->id;
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
				$venues = $array["venues"];
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
					if (count($venues) > 0) {
						foreach ($r as $key => &$value)
							if (!in_array($value["id"], $venues))
								$response_venues["response"]["venues"][] = $r[$key];
							else
								unset($r[$key]); // remove venue duplicada
						unset($value);
					} else {
						$response_venues["response"]["venues"] = $r;
					}
					$venues = array_merge($venues, $array["venues"]);
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

	} else {
		$pbar->hide();
		echo TEMPLATE1 . '<p><b>Erro ' . $json->meta->code . ':</b> ' . $json->meta->errorType . '</p>
<p><b>Detalhe:</b> ' . $json->meta->errorDetail . '</p>
' . TEMPLATE2;
		exit;
	}

	return $response;
}

function setLocalCache($key, $data) {
	print('<script type="text/javascript">'."\n\r".'	localStorage.setItem(\''.$key.'\', \''.$data.'\');'."\n\r".'</script>');
	print str_pad('', intval(ini_get('output_buffering'))) . "\n\r";
	flush();
}

?>