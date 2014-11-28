<?php

/**
 * List Venues Editor
 *
 * Edição de venues a partir dos campos e dados recebidos do load.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2014
 * @version    2.2.2
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/edit.php
 * @since      File available since Release 0.5
 * @license    GPLv3 <http://www.gnu.org/licenses/gpl.txt>
 */

if (!isset($_SESSION))
	session_start();
if ((isset($_SESSION["oauth_token"])) && ($_SESSION["file"] != null)) {
	$file = $_SESSION["file"];
	$venuesIds = $_SESSION["venuesIds"];
	$campos = $_SESSION["campos"];
	if (isset($_SESSION["venues"]))
		unset($_SESSION["venues"]);
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
<script src="js/4sq.js"></script>
</head>
<body class="tundra">
<header>
	<h2>Editar venues</h2>
</header>
<article>
	<p>Antes de salvar suas propostas de altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialogGuia()">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
</article>
<article>
	<div id="mapa"></div>
</article>
<article>
<div id="listContainer">
<?php
$totalCampos = 0;

if ($campos != null) {
	if (in_array("nome", $campos)) {
		$editName = true;
		$totalCampos++;
	} else {
		$editName = false;
	}
	if (in_array("endereco", $campos)) {
		$editAddress = true;
		$totalCampos++;
	} else {
		$editAddress = false;
	}
	if (in_array("ruatransversal", $campos)) {
		$editCross = true;
		$totalCampos++;
	} else {
		$editCross = false;
	}
	if (in_array("bairro", $campos)) {
		$editNeighborhood = true;
		$totalCampos++;
	} else {
		$editNeighborhood = false;
	}
	if (in_array("cidade", $campos)) {
		$editCity = true;
		$totalCampos++;
	} else {
	$editCity = false;
	}
	if (in_array("estado", $campos)) {
		$editState = true;
		$totalCampos++;
	} else {
		$editState = false;
	}
	if (in_array("codigopostal", $campos)) {
		$editZip = true;
		$totalCampos++;
	} else {
		$editZip = false;
	}
	if (in_array("dentro", $campos)) {
		$editParentId = true;
		$totalCampos++;
	} else {
		$editParentId = false;
	}
	if (in_array("telefone", $campos)) {
		$editPhone = true;
		$totalCampos++;
	} else {
		$editPhone = false;
	}
	if (in_array("sitedaweb", $campos)) {
		$editUrl = true;
		$totalCampos++;
	} else {
		$editUrl = false;
	}
	if (in_array("twitter", $campos)) {
		$editTwitter = true;
		$totalCampos++;
	} else {
		$editTwitter = false;
	}
	if (in_array("facebook", $campos)) {
		$editFacebook = true;
		$totalCampos++;
	} else {
		$editFacebook = false;
	}
	if (in_array("descricao", $campos)) {
		$editDesc = true;
		$totalCampos++;
	} else {
		$editDesc = false;
	}
	if (in_array("latlng", $campos)) {
		$editVenuell = true;
		$totalCampos++;
	} else {
		$editVenuell = false;
	}
} else {
	$editName = false;
	$editAddress = false;
	$editCross = false;
	$editNeighborhood = false;
	$editCity = false;
	$editState = false;
	$editZip = false;
	$editParentId = false;
	$editPhone = false;
	$editUrl = false;
	$editTwitter = false;
	$editFacebook = false;
	$editVenuell = false;
	$editDesc = false;
}

$ajusteInput = 11 - $totalCampos;

$i = 0;

foreach ($file as $f) {
	$i++;

	if (isset($venuesIds[$i - 1])) {
		echo '<section id="linha', $i - 1, '" class="row">', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-data" method="post">', chr(10);

		$venue = $venuesIds[$i - 1];
		echo '<div class="selectbox"><input name="selecao" data-dojo-type="dijit/form/CheckBox" value="', $i - 1, '" onChange="atualizarItensMenuMais(this.value)"></div>', chr(10);

		echo '<input type="hidden" name="venue" value="', $venue, '"><span id="info', $i - 1, '"><a id="venLnk', $i - 1, '" href="', $f, '" target="_blank" style="margin-left: 23px; margin-right: 5px; vertical-align: -1px;">';
		if (count($file) < 10)
			echo $i;
		else if (count($file) < 100)
			echo str_pad($i, 2, "0", STR_PAD_LEFT);
		else
			echo str_pad($i, 3, "0", STR_PAD_LEFT);
		echo '</a></span>', chr(10);

		echo '<span id="icone', $i - 1, '"><img id=catImg', $i, ' src="https://foursquare.com/img/categories_v2/none_bg_32.png" style="height: 22px; width: 22px; margin-left: 0px"></span>', chr(10);

		if ($editName) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value=" " placeHolder="Nome" style="width: ', 11 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Nome">', chr(10);
		} else {
			echo '<input type="hidden" name="name">', chr(10);
		}

		if ($editAddress) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value=" " placeHolder="Endere&ccedil;o" style="width: ', 11 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Endere&ccedil;o">', chr(10);
		}

		if ($editCross) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value=" " placeHolder="Rua transversal" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Rua transversal">', chr(10);
		}
	
		if ($editNeighborhood) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="neighborhood" maxlength="51" value=" " placeHolder="Bairro" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Bairro">', chr(10);
		}

		if ($editCity) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value=" " placeHolder="Cidade" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Cidade">', chr(10);
		}

		if ($editState) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="state" maxlength="30" value=" " placeHolder="UF" style="width: 2.5em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="UF">', chr(10);	 
		}

		if ($editZip) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value=" " placeHolder="C&oacute;digo postal" style="width: 6em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="C&oacute;digo postal">', chr(10);
		}
	
		if ($editParentId) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="parentId" maxlength="24" value=" " placeHolder="Dentro" style="width: 14em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Dentro">', chr(10);
		}

		if ($editPhone) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value=" " placeHolder="Telefone" style="width: 7em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Telefone">', chr(10);
		}

		if ($editUrl) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value=" " placeHolder="Website" style="width: ', 8 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Website">', chr(10);
		}
	
		if ($editTwitter) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value=" " placeHolder="Twitter" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Twitter">', chr(10);
		}
	
		if ($editFacebook) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="facebook" maxlength="51" value=" " placeHolder="Facebook" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Facebook">', chr(10);
		}

		if ($editDesc) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value=" " placeHolder="Descri&ccedil;&atilde;o" style="width: ', 8 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Descri&ccedil;&atilde;o">', chr(10);
		}

		if ($editVenuell) {
			echo '<input type="text" dojoType="dijit.form.TextBox" name="venuell" maxlength="402" value=" " placeHolder="Lat/Long" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')" data-name-ptbr="Lat/Long">', chr(10);
		} else {
			echo '<input type="hidden" name="venuell">', chr(10);
		}
	
		echo '<input type="hidden" id="cid', $i - 1, '" name="categoryId"><input type="hidden" id="cna', $i - 1, '" name="categoryName"><input type="hidden" id="cic', $i - 1, '" name="categoryIcon"><input type="hidden" id="vdt', $i - 1, '" name="createdAt"><input type="hidden" id="vcc', $i - 1, '" name="checkinsCount"><input type="hidden" id="vuc', $i - 1, '" name="usersCount"><input type="hidden" id="vtc', $i - 1, '" name="tipCount"><input type="hidden" id="vpc', $i - 1, '" name="photosCount"><input type="hidden" id="vhc', $i - 1, '" name="likesCount"><input type="hidden" id="vlc', $i - 1, '" name="listedCount"><input type="hidden" id="vic', $i - 1, '" name="isClosed"><input type="hidden" id="vip', $i - 1, '" name="isPrivate"><input type="hidden" id="vid', $i - 1, '" name="isDeleted"><input type="hidden" id="vrf', $i - 1, '" name="verified">', chr(10);
		echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</section>', chr(10);
	}
}
?>
</div>
</article>
<!-- Botoes Salvar, Cancelar e Mais -->
<article>
	<div id="fixedtray">
		<button id="saveButton" dojoType="dijit.form.Button" type="submit" name="saveButton" onclick="javascript:showDialogComment(this.name)" style="float: left; padding-right: 3px;" disabled>Salvar</button>
		<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="location.href='main.php'" name="cancelButton" style="float: left; padding-right: 3px;">Cancelar</button>
		<div id="dropdownButtonContainer" style="float: left"></div>
	</div>
</article>
<!-- Janela de Edicao das Categorias -->
<div data-dojo-type="dijit.Dialog" id="dlg_cats" data-dojo-props='title:"Categorias"'>
	<div id="catsContainer"></div>
	<div id="treeContainer"></div>
	<span class="checkbox">
		<div class="editAllCheckbox" style="width: 20em;">
			<input id="editAllCheckbox" name="editAllCheckbox" dojoType="dijit.form.CheckBox" value="editAllCategories">
				<label for="editAllCheckbox">
					Aplicar categoria(s) a todas as venues
				</label>
		</div>
	</span>
	<span style="clear: both; display: block;">
		<button id="saveCatsButton" dojoType="dijit.form.Button" type="button" onclick="salvarCategorias()" name="saveCatsButton">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_cats').hide(); }">Cancelar</button>
	</span>
	<div id="venueIndex" style="display: none"></div><div id="catsIds" style="display: none"></div><div id="catsIcones" style="display: none"></div>
</div>
<!-- Barra de Progresso ao Salvar -->
<div data-dojo-type="dijit.Dialog" id="dlg_save" data-dojo-props='title:"Salvando venues..."'>
	<div dojoType="dijit.ProgressBar" style="width:300px" jsId="jsProgress"
id="saveProgress">
	</div>
</div>
<!-- Janela de Digitacao de Comentario -->
<div id="dlg_comment" data-dojo-type="dijit.Dialog" data-dojo-props="title:'Coment&aacute;rios'" style="display:none; width: 356px;">
	<div class="dijitDialogPaneContentArea">
		<textarea id="textareaComment" name="textareaComment" data-dojo-type="dijit/form/Textarea" maxLength="200" trim="true"></textarea>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="submit" id="saveCommentButton" data-dojo-props="onClick:function(){ (actionButton == 'saveButton') ? salvarVenues() : sinalizarVenues(actionButton); }">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_comment').onCancel(); }">Cancelar</button>
	</div>
</div>
<!-- Janela de Edicao Multipla -->
<div id="dlg_editField" data-dojo-type="dijit.Dialog" data-dojo-props="title:'Edi&ccedil;&atilde;o M&uacute;ltipla'" style="display:none; width: 275px;">
	<div id="editFieldContainer" class="dijitDialogPaneContentArea">
		<div class="row">
			<span class="label"><label for="selectEditField">Campo:</label></span>
			<select data-dojo-id="selectEditField" name="selectEditField" id="selectEditField" data-dojo-type="dijit/form/Select" style="width: 15.1em; margin-bottom: 9px" data-dojo-props="onChange:function(){ dijit.byId('inputEditField').attr('value', ''); dijit.byId('inputEditField').attr('placeHolder', this.attr('displayedValue')); }"></select>
		</div>
		<div class="row">
			<span class="label"><label for="selectEditField">Valor:</label></span>
			<input type="text" id="inputEditField" name="inputEditField1" dojoType="dijit.form.TextBox" trim="true" style="width: 15.1em"/>
		</div>
	</div>
	<div class="dijitDialogPaneActionBar">
		<button data-dojo-type="dijit.form.Button" type="submit" id="editFieldOkButton" data-dojo-props="onClick:function(){ editField(dijit.byId('selectEditField').value, dijit.byId('inputEditField').value); }">OK</button>
		<button data-dojo-type="dijit.form.Button" type="button" id="editFieldApplyButton" data-dojo-props="onClick:function(){ editField(dijit.byId('selectEditField').value, dijit.byId('inputEditField').value); }">Aplicar</button>
		<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){ dijit.byId('dlg_editField').onCancel(); }">Cancelar</button>
	</div>
</div>
</body>
</html>
