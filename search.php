<?php

/**
 * Search Venues
 *
 * Pesquisa venues utilizando a API
 *
 * @category	 Foursquare
 * @package		 Foursquare-Mass-Editor-Tools
 * @author		 Elio Gavlinski <gavlinski@gmail.com>
 * @copyright	 Copyleft (c) 2012
 * @version		 1.0
 * @link			 https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/search.php
 * @since			 File available since Release 1.5
 */

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
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
');
define("HBODY", '</head>
<body class="claro">
');
define("PESQUISANDO", LINKS . HBODY . '<div id="carregando">Pesquisando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
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
	$params["ll"] = utf8_encode($_POST["ll"]);
if (isset($_POST["near"]))
	$params["near"] = $_POST["near"];
if (isset($_POST["categoryId"]))
	$params["categoryId"] = $_POST["categoryId"];
if (isset($_POST["query"]))
	$params["query"] = $_POST["query"];
if (isset($_POST["limit"]))
	$params["limit"] = $_POST["limit"];
if (isset($_POST["intent"]))
	$params["intent"] = $_POST["intent"];
if (isset($_POST["radius"]))
	$params["radius"] = $_POST["radius"];

$data = pesquisarVenues($params);
//print_r($data);

if (count($file) > 0) {
	$_SESSION["file"] = filtrarArray($file);
	//print_r(filtrarArray($file));
	setLocalCache("txt", implode('%0A,', $_SESSION["file"]));
	$_SESSION["venues"] = filtrarArray($venues);
	//echo '<br><br>';
	//print_r(filtrarArray($venues));
	$_SESSION["campos"] = $_POST["campos4"];
	setLocalCache("venues", str_replace(array('"', "'"), array('\"', "\'"), $data));
	//exit;
	echo EDIT;
}
echo ERRO99;

function filtrarArray($array) {
	return array_values(array_unique($array));
}

function pesquisarVenues($params) {
	global $venues;
	$venues = array();
	global $file;
	$file = array();
	$i = 0;
	
	require_once("ProgressBar.Class.php");

	echo PESQUISANDO;
	$p = new ProgressBar();
	echo '<div style="width: 400px;">' . "\r\n";
	$p->render();
	echo '</div>' . "\r\n";

	require_once("FoursquareAPI.Class.php");

	/*** Set client key and secret ***/
	$client_key = "3EZPQCWMPTP0TLV4SJNPOLMWJB4UVCBGMADXWQCYFU3MPIQZ";
	$client_secret = "J2310KS05Z50PU44DUC0T0HPEYM2CEQKBBPROAGXMBACZRZG";

	/*** Load the Foursquare API library ***/
	$foursquare = new FoursquareAPI($client_key, $client_secret);
	$foursquare -> SetAccessToken($_SESSION["oauth_token"]);
	
	/*** Perform a request to a authenticated-only resource ***/
	$response = $foursquare -> GetPrivate("venues/search", $params);
	$json = json_decode($response);
	
	if ($json->meta->code == 200) {
		$size = count($json->response->venues);

		foreach ($json->response->venues as $venue) {
			if (property_exists($venue, "id"))
				$venues[$i] = $venue->id;
			if (property_exists($venue, "canonicalUrl"))
				$file[$i] = $venue->canonicalUrl;
			$i++;
			$p->setProgressBarProgress($i*100/$size);
			usleep(10000*0.1);
		}
	} else {
		$p->hide();
		echo TEMPLATE1 . '<p>Erro ' . $json->meta->code . ': ' . $json->meta->errorType . '</p>
<p>Detalhe: ' . $json->meta->errorDetail . '</p>
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