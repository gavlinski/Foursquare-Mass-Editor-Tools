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
$_SESSION["oauth_token"] = $_POST["oauth_token"];
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
define("PESQUISANDO", LINKS . HBODY . '<div id="pesquisando">Pesquisando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">dojo.require("dijit.form.Button");</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.html">
' . LINKS . '</head>
<body>
<p>Erro na leitura dos dados.</p>
</body>
</html>');
define("EDIT", '<script type="text/javascript">
	window.location = "edit.php"
</script>;');

if (isset($_POST["limit"])) {
	$ll = $_POST["ll"];
	//$near = $_POST["near"];
	//$categoryId = $_POST["categoryId"];
	//$query = $_POST["query"];
	$limit = $_POST["limit"];
	//$intent = $_POST["intent"];
	//$radius = $_POST["radius"];
	//$_SESSION["file"] = filtrarArray($lista);
	
	$_SESSION["campos"] = $_POST["campos4"];
	print_r($_SESSION["campos"]);
	//echo '<br><br>';
	//print_r($venues);
	exit;
}
echo ERRO99;

function filtrarArray($array) {
	return array_values(array_unique($array));
}

function pesquisarVenues($lines) {
	$ret = array();
	global $venues;
	$venues = array();
	$i = 0;
	
	require_once 'ProgressBar.Class.php';

	echo CARREGANDO;
	$p = new ProgressBar();
	echo '<div style="width: 400px;">' . "\r\n";
	$p->render();
	echo '</div>' . "\r\n";
}

?>