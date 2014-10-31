<?php

/**
 * CSV Venues Loader
 *
 * Carrega venues a partir de um arquivo CSV
 *
 * @category	 Foursquare
 * @package		 Foursquare-Mass-Editor-Tools
 * @author		 Elio Gavlinski <gavlinski@gmail.com>
 * @copyright	 Copyleft (c) 2011-2012
 * @version		 2.2.0
 * @link			 https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/load_csv.php
 * @since			 File available since Release 1.1
 * @license		 GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */
 
if (!isset($_SESSION))
	session_start();
if (!isset($_SESSION["oauth_token"])) {
	header('Location: index.php');
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta http-equiv="cache-control" content="no-cache">
<meta http-equiv="pragma" content="no-cache">
<title>Carregando...</title>
<?php
define("VERSION", "CSV Venues Editor 2.1.1");
define("LINKS", '<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css">
');
define("HBODY", '</head>
<body class="tundra">
');
define("CARREGANDO", LINKS . HBODY . '<div id="carregando">Carregando venues&hellip;</div>
');
define("TEMPLATE1", '<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script>
dojo.require("dijit.form.Button");
document.getElementById("pb").style.display = "none";
document.getElementById("carregando").style.display = "none";
</script>
');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" style="margin-left: 0px;">Voltar</button></p>
</body>
</html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p>
<p>Reduza a quantidade de linhas do arquivo e tente novamente.</p>
'. TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>Erro na leitura do cabe&ccedil;alho ou conjunto de caracteres inv&aacute;lido.</p>
<p>Verifique o arquivo CSV e tente novamente.</p>
' . TEMPLATE2);
define("ERRO99", '<meta http-equiv="refresh" content="5; url=index.php">
' . LINKS . '</head>
<body>
<p>Erro na leitura dos dados.</p>
</body>
</html>');
define("EDIT", '<script>
	window.location = "edit_csv.php"
</script>;');
define("FLAG", '<script>
	window.location = "flag_csv.php"
</script>;');

$csv = $_FILES['csv']['tmp_name'];
$filename = $_FILES['csv']['name'];

if (is_uploaded_file($csv)) {
	require "CsvToArray.Class.php";
	$file = CsvToArray::open($csv);
	if (count($file) > 500) {
		echo ERRO01;
		exit;
	}
	if (array_key_exists("venue", $file[0]) == false) {
		echo ERRO02;
		exit;
	}
	$_SESSION["file"] = $file;
	if (stripos($filename, "flag") !== false)
		echo FLAG;
	else
		echo EDIT;
} else {
	echo ERRO99;
	exit();
}
?>