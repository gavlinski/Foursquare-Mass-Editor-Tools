<?php

/**
 * CSV Venues Editor
 *
 * Edição de venues a partir dos dados recebidos do load_csv.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2018
 * @version    2.3.0
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/edit_csv.php
 * @since      File available since Release 0.3
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
	<h2>Editar venues</h2>
</header>
<article>
	<p>Antes de salvar suas propostas de altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialogGuia();">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
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
if (array_key_exists("neighborhood", $file[0])) {
	$hasNeighborhood = true;
} else {
	$hasNeighborhood = false;
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
	$hasFacebook = true;
} else {
	$hasFacebook = false;
}
if (array_key_exists("instagram", $file[0])) {
	$hasInstagram = true;
} else {
	$hasInstagram = false;
}
if (array_key_exists("venuell", $file[0])) {
	$hasVenuell = true;
} else {
	$hasVenuell = false;
}
if (array_key_exists("description", $file[0])) {
	$hasDescription = true;
} else {
	$hasDescription = false;
}
if (array_key_exists("menu", $file[0])) {
	$hasMenu = true;
} else {
	$hasMenu = false;
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
		echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value="', $name, '" placeHolder="Nome" style="width: 11em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasAddress) {
		$address = htmlentities($f['address'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value="', $address, '" placeHolder="Endere&ccedil;o" style="width: 11em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasCrossStreet) {
		$crossStreet = htmlentities($f['crossStreet'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="128" value="', $crossStreet, '" placeHolder="Rua transversal" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasNeighborhood) {
		$neighborhood = htmlentities($f['neighborhood'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="neighborhood" maxlength="128" value="', $neighborhood, '" placeHolder="Bairro" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasCity) {
		$city = htmlentities($f['city'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value="', $city, '" placeHolder="Cidade" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasState) {
		$state = htmlentities($f['state'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="state" maxlength="30" value="', $state, '" placeHolder="UF" style="width: 2.5em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasZip) {
		$zip = $f['zip'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value="', $zip, '" placeHolder="C&oacute;digo postal" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasParentId) {
		$parentId = $f['parentId'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="parentId" maxlength="24" value="', $parentId, '" placeHolder="Dentro" style="width: 14em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasPhone) {
		$phone = $f['phone'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value="', $phone, '" placeHolder="Telefone" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasUrl) {
		$url = $f['url'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value="', $url, '" placeHolder="Website" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasTwitter) {
		$twitter = $f['twitter'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value="', $twitter, '" placeHolder="Twitter" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasFacebook) {
		$facebook = $f['facebook'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="facebook" maxlength="51" value="', $facebook, '" placeHolder="Facebook" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasInstagram) {
		$instagram = $f['instagram'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="instagram" maxlength="51" value="', $instagram, '" placeHolder="Instagram" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasVenuell) {
		$venuell = $f['venuell'];
		//if (($venuell != '') && ($venuell != ' ')) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="venuell" maxlength="402" value="', $venuell, '" placeHolder="Lat/Long" style="width: 12em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
		//} else {
			//echo '<input type="text" dojoType="dijit.form.TextBox" name="venuell" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
		//}
	}

	if ($hasDescription) {
		$description = htmlentities($f['description'], ENT_QUOTES, 'utf-8');
		echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value="', $description, '" placeHolder="Descri&ccedil;&atilde;o" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasMenu) {
		$menu = $f['menu'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="menu" maxlength="256" value="', $menu, '" placeHolder="Menu" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	if ($hasCategoryId) {
		$categoryId = $f['categoryId'];
		//if (($categoryId != '') && ($categoryId != ' ')) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" maxlength="75" value="', $categoryId, '" placeHolder="Categoria(s)" style="width: 14em; margin-left: 5px;" readonly>', chr(10);
		//} else {
			//echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" placeHolder="Categorias" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
		//}
	}
	
	if ($hasPrimaryCategoryId) {
		$primaryCategoryId = $f['primaryCategoryId'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="primaryCategoryId" maxlength="24" value="', $primaryCategoryId, '" placeHolder="Categoria Prim&aacute;ria" style="width: 14em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasAddCategoryIds) {
		$addCategoryIds = $f['addCategoryIds'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="addCategoryIds" maxlength="75" value="', $addCategoryIds, '" placeHolder="Adicionar Categoria(s)" style="width: 14em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}
	
	if ($hasRemoveCategoryIds) {
		$removeCategoryIds = $f['removeCategoryIds'];
		echo '<input type="text" dojoType="dijit.form.TextBox" name="removeCategoryIds" maxlength="75" value="', $removeCategoryIds, '" placeHolder="Remover Categoria(s)" style="width: 14em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
	}

	echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</section>', chr(10);
}
?>
</div>
</article>
<article>
	<div id="fixedtray">
		<button id="saveButton" dojoType="dijit.form.Button" type="submit" name="saveButton" onclick="salvarVenues()" style="float: left; padding-right: 3px;">Salvar</button>
		<button id="backButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="backButton" style="float: left">Voltar</button>
		<div id="dropdownButtonContainer2" style="float: left"></div>
	</div>
</article>
</body>
</html>
