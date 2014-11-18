<?php

/**
 * CSV Venues Flag
 *
 * Flag de venues inválidas a partir com os dados recebidos do load_csv.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2012
 * @version    2.2.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/flag_csv.php
 * @since      File available since Release 1.1
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

if (!isset($_SESSION))
	session_start();
if (isset($_SESSION["oauth_token"])) {
	$oauth_token = $_SESSION["oauth_token"];
	$file = $_SESSION["file"];
} else {
	header('Location: index.php'); /* Redirect browser */
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<title>Elio Tools</title>
<meta charset="utf-8">
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="js/dijit/themes/tundra/tundra.css">
<link rel="stylesheet" type="text/css" href="estilo.css">
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script src="js/4sq_csv.js"></script>
</head>
<body class="tundra">
<header>
	<h2>Sinalizar venues</h2>
</header>
<article>
	<p>Antes de sinalizar as venues, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialogGuia();">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
</article>
<article>
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
	$hasCrossStreet = true;
} else {
	$hasCrossStreet = false;
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
if (array_key_exists("parentId", $file[0])) {
	$hasParentId = true;
} else {
	$hasParentId = false;
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
if (array_key_exists("twitter", $file[0])) {
	$hasTwitter = true;
} else {
	$hasTwitter = false;
}
if (array_key_exists("facebook", $file[0])) {
	$hasfacebook = true;
} else {
	$hasfacebook = false;
}
if (array_key_exists("description", $file[0])) {
	$hasDescription = true;
} else {
	$hasDescription = false;
}
if (array_key_exists("venuell", $file[0])) {
	$hasVenuell = true;
} else {
	$hasVenuell = false;
}
if (array_key_exists("categoryId", $file[0])) {
	$hasCategoryId = true;
} else {
	$hasCategoryId = false;
}
if (array_key_exists("primaryCategoryId", $file[0])) {
	$hasPrimaryCategoryId = true;
} else {
	$hasPrimaryCategoryId = false;
}
if (array_key_exists("addCategoryIds", $file[0])) {
	$hasAddCategoryIds = true;
} else {
	$hasAddCategoryIds = false;
}
if (array_key_exists("removeCategoryIds", $file[0])) {
	$hasRemoveCategoryIds = true;
} else {
	$hasRemoveCategoryIds = false;
}

$i = 0;

foreach ($file as $f) {
	$i++;

	echo '<section class="row">', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-data" method="post">', chr(10);

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
		$name = htmlentities($f['name'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="name" value="', $name, '" placeHolder="Nome" style="width: 11em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasAddress) {
		$address = htmlentities($f['address'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="address" value="', $address, '" placeHolder="Endere&ccedil;o" style="width: 11em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCrossStreet) {
		$crossStreet = htmlentities($f['crossStreet'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" value="', $crossStreet, '" placeHolder="Rua transversal" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCity) {
		$city = htmlentities($f['city'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="city" value="', $city, '" placeHolder="Cidade" style="width: 7em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasState) {
		$state = htmlentities($f['state'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="state" value="', $state, '" placeHolder="UF" style="width: 2.5em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasZip) {
		$zip = $f['zip'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" value="', $zip, '" placeHolder="C&oacute;digo postal" style="width: 7em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasParentId) {
		$parentId = $f['parentId'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="parentId" value="', $parentId, '" placeHolder="Dentro" style="width: 14em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasPhone) {
		$phone = $f['phone'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" value="', $phone, '" placeHolder="Telefone" style="width: 7em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasUrl) {
		$url = $f['url'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="url" value="', $url, '" placeHolder="Website" style="width: 8em; margin-left: 5px;" disabled>', chr(10);
	}
	
	if ($hasTwitter) {
		$twitter = $f['twitter'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" value="', $twitter, '" placeHolder="Twitter" style="width: 7em; margin-left: 5px;" disabled>', chr(10);
	}
	
	if ($hasfacebook) {
		$facebook = $f['facebook'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="facebook" value="', $facebook, '" placeHolder="Facebook" style="width: 7em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasDescription) {
		$description = htmlentities($f['description']);
		echo '<input type="text" dojoType="dijit.form.TextBox" name="description" value="', $description, '" placeHolder="Descri&ccedil;&atilde;o" style="width: 8em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasVenuell) {
		$venuell = $f['venuell'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="venuell" value="', $venuell, '" placeHolder="Lat/Long" style="width: 12em; margin-left: 5px;" disabled>', chr(10);
	}

	if ($hasCategoryId) {
		$categoryId = $f['categoryId'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" value="', $categoryId, '" placeHolder="Categoria(s)" style="width: 14em; margin-left: 5px;" disabled>', chr(10);
	}
	
	if ($hasPrimaryCategoryId) {
		$primaryCategoryId = $f['primaryCategoryId'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="primaryCategoryId" value="', $primaryCategoryId, '" placeHolder="Categoria Prim&aacute;ria" style="width: 14em; margin-left: 5px;" disabled>', chr(10);
	}
	
	if ($hasAddCategoryIds) {
		$addCategoryIds = $f['addCategoryIds'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="addCategoryIds" value="', $addCategoryIds, '" placeHolder="Adicionar Categoria(s)" style="width: 14em; margin-left: 5px;" disabled>', chr(10);
	}
	
	if ($hasRemoveCategoryIds) {
		$removeCategoryIds = $f['removeCategoryIds'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="removeCategoryIds" value="', $removeCategoryIds, '" placeHolder="Remover Categoria(s)" style="width: 14em; margin-left: 5px;" disabled>', chr(10);
	}

	echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</section>', chr(10);
}
?>
</div>
</article>
<article>
	<div id="dropdownButtonContainer1" style="float: left; padding-right: 3px; margin-left: 0px; margin-bottom: 15px"></div>
	<!--<button id="flagButton" dojoType="dijit.form.Button" type="submit" name="flagButton" onclick="sinalizarVenues()" style="float: left; padding-right: 3px;">Flag</button>-->
	<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="cancelButton" style="float: left">Cancelar</button>
	<div id="dropdownButtonContainer2" style="float: left"></div>
</article>
</body>
</html>
