<?php

/**
 * CSV Venues Flag
 *
 * Flag de venues inválidas a partir com os dados recebidos do load_csv.php
 *
 * @category	 Foursquare
 * @package		 Foursquare-Mass-Editor-Tools
 * @author		 Elio Gavlinski <gavlinski@gmail.com>
 * @copyright	 Copyleft (c) 2011-2012
 * @version		 1.1
 * @link			 https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/flag_csv.php
 * @since			 File available since Release 1.1
 * @license		 GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

session_start();
if (isset($_SESSION["oauth_token"])) {
	$oauth_token = $_SESSION["oauth_token"];
	$file = $_SESSION["file"];
} else {
	header('Location: index.php'); /* Redirect browser */
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Elio Tools</title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript" src="js/4sq_csv.js"></script>
</head>
<body class="claro">
<h2>Sinalizar venues</h2>
<p>Antes de sinalizar as venues, n&atilde;o deixe de ler nosso <a href="javascript:showDialog_guia();">guia de estilo</a> e as <a href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
<div id="listContainer">
<?php
if (array_key_exists("name", $file[0])) {
	$hasName = true;
} else {
	$hasName = false;
}
if (array_key_exists("address", $file[0])) {
	$hasAddress = true;
} else {
	$hasAddress = false;
}
if (array_key_exists("crossStreet", $file[0])) {
	$hasCross = true;
} else {
	$hasCross = false;
}
if (array_key_exists("city", $file[0])) {
	$hasCity = true;
} else {
	$hasCity = false;
}
if (array_key_exists("state", $file[0])) {
	$hasState = true;
} else {
	$hasState = false;
}
if (array_key_exists("zip", $file[0])) {
	$hasZip = true;
} else {
	$hasZip = false;
}
if (array_key_exists("twitter", $file[0])) {
	$hasTwitter = true;
} else {
	$hasTwitter = false;
}
if (array_key_exists("phone", $file[0])) {
	$hasPhone = true;
} else {
	$hasPhone = false;
}
if (array_key_exists("url", $file[0])) {
	$hasUrl = true;
} else {
	$hasUrl = false;
}
if (array_key_exists("description", $file[0])) {
	$hasDesc = true;
} else {
	$hasDesc = false;
}
if (array_key_exists("ll", $file[0])) {
	$hasLl = true;
} else {
	$hasLl = false;
}
if (array_key_exists("categoryId", $file[0])) {
	$hasCategoryId = true;
} else {
	$hasCategoryId = false;
}

$i = 0;

foreach ($file as $f) {
	$i++;

	echo '<div class="row">', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-data" method="post">', chr(10);

	$venue = $f['venue'];
	echo '<input type="hidden" name="venue" value="', $venue, '"><a href="https://foursquare.com/v/', $venue, '" target="_blank"';
	if ($hasCategoryId) {
		echo ' style="margin-right: 5px;"';
	}
	echo '>';
	if (count($file) < 10)
		echo $i;
	else if (count($file) < 100)
		echo str_pad($i, 2, "0", STR_PAD_LEFT);
	else
		echo str_pad($i, 3, "0", STR_PAD_LEFT);
	echo '</a>', chr(10);
	
	if ($hasCategoryId) {
		echo '<span id="icone', $i - 1, '"><img id=catImg', $i, ' src="https://foursquare.com/img/categories_v2/none_bg_32.png" style="height: 22px; width: 22px; margin-left: 0px"></span>', chr(10);
	}

	if ($hasName) {
		$name = htmlentities($f['name']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="name" value="', $name, '" placeHolder="Nome" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasAddress) {
		$address = htmlentities($f['address']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="address" value="', $address, '" placeHolder="Endere&ccedil;o" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCross) {
		$crossStreet = htmlentities($f['crossStreet']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" value="', $crossStreet, '" placeHolder="Rua Cross" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCity) {
		$city = htmlentities($f['city']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="city" value="', $city, '" placeHolder="Cidade" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasState) {
		$state = htmlentities($f['state']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="state" value="', $state, '" placeHolder="UF" style="width: 2.5em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasZip) {
		$zip = $f['zip'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" value="', $zip, '" placeHolder="CEP" style="width: 6em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasTwitter) {
		$twitter = $f['twitter'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" value="', $twitter, '" placeHolder="Twitter" style="width: 8em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasPhone) {
		$phone = $f['phone'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" value="', $phone, '" placeHolder="Telefone" style="width: 8em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasUrl) {
		$url = $f['url'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="url" value="', $url, '" placeHolder="Website" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasDesc) {
		$description = htmlentities($f['description']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="description" value="', $description, '" placeHolder="Descri&ccedil;&atilde;o" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasLl) {
		$ll = $f['ll'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" value="', $ll, '" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCategoryId) {
		$categoryId = htmlentities($f['categoryId']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" value="', $categoryId, '" placeHolder="Categorias" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<div>
<div id="dropdownButtonContainer1" style="float: left; padding-right: 3px; margin-left: 0px; margin-bottom: 15px"></div>
</div>
<!--<button id="flagButton" dojoType="dijit.form.Button" type="submit" name="flagButton" onclick="sinalizarVenues()" style="float: left; padding-right: 3px;">Flag</button>-->
<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="cancelButton" style="float: left">Cancelar</button>
<div id="dropdownButtonContainer2" style="float: left"></div>
</div>
</body>
</html>
