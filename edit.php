<?php

/**
 * List Venues Editor
 *
 * Edita venues de acordo com os campos e dados recebidos do load.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2012
 * @version    1.1
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/edit.php
 * @since      File available since Release 0.5
 */

session_start();
if (isset($_SESSION["oauth_token"])) {
	$oauth_token = $_SESSION["oauth_token"];
	$file = $_SESSION["file"];
	$venues = $_SESSION["venues"];
	$campos = $_SESSION["campos"];
} else {
  header('Location: index.html'); /* Redirect browser */
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<title>Superuser Tools - foursquare</title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/>
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">var oauth_token = "<?= $oauth_token ?>";</script>
<script type="text/javascript" src="js/4sq.js"></script>
</head>
<body class="claro">
<h2>Editar venues</h2>
<p>Antes de salvar suas altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialog_guia();">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
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
	if (in_array("ruacross", $campos)) {
	  $editCross = true;
	  $totalCampos++;
	} else {
	  $editCross = false;
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
	if (in_array("cep", $campos)) {
	  $editZip = true;
	  $totalCampos++;
	} else {
	  $editZip = false;
	}
	if (in_array("twitter", $campos)) {
	  $editTwitter = true;
	  $totalCampos++;
	} else {
	  $editTwitter = false;
	}
	if (in_array("telefone", $campos)) {
	  $editPhone = true;
	  $totalCampos++;
	} else {
	  $editPhone = false;
	}
	if (in_array("website", $campos)) {
	  $editUrl = true;
	  $totalCampos++;
	} else {
	  $editUrl = false;
	}
	if (in_array("descricao", $campos)) {
	  $editDesc = true;
	  $totalCampos++;
	} else {
	  $editDesc = false;
	}
	if (in_array("latlong", $campos)) {
	  $editLl = true;
	  $totalCampos++;
	} else {
	  $editLl = false;
	}
}

$ajusteInput = 11 - $totalCampos;

$ufs = array("AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO");

$i = 0;

foreach ($file as $f) {
  $i++;

  echo '<div class="row">', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-data" method="post">', chr(10);

  $venue = $venues[$i - 1];
  echo '<input type="hidden" name="venue" value="', $venue, '"><span id="info', $i - 1, '"><a id="venLnk', $i - 1, '" href="', $f, '" target="_blank" style="margin-right: 5px;">';
  if (count($file) < 10)
    echo $i;
  else if (count($file) < 100)
    echo str_pad($i, 2, "0", STR_PAD_LEFT);
  else
    echo str_pad($i, 3, "0", STR_PAD_LEFT);
  echo '</a></span>', chr(10);

  echo '<span id="icone', $i - 1, '"><img id=catImg', $i, ' src="http://foursquare.com/img/categories/none.png" style="height: 22px; width: 22px; margin-left: 0px"></span>', chr(10);

  if ($editName) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value=" " placeHolder="Nome" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  } else {
    echo '<input type="hidden" name="name">', chr(10);
  }

  if ($editAddress) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value=" " placeHolder="Endere&ccedil;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editCross) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value=" " placeHolder="Rua Cross" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editCity) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value=" " placeHolder="Cidade" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editState) {
    echo '<select dojoType="dijit.form.ComboBox" name="state" style="width: 4em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')"><option value=""></option>';
    for ($j = 0; $j <= 26; $j++) {
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo '</select>', chr(10);
  }

  if ($editZip) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value=" " placeHolder="CEP" style="width: 6em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editTwitter) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value=" " placeHolder="Twitter" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editPhone) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value=" " placeHolder="Telefone" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editUrl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value=" " placeHolder="Website" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editDesc) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value=" " placeHolder="Descri&ccedil;&atilde;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editLl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" maxlength="402" value=" " placeHolder="Lat/Long" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }
  
  echo '<input type="hidden" id="cid', $i - 1, '" name="categoryId"><input type="hidden" id="cna', $i - 1, '" name="categoryName"><input type="hidden" id="cic', $i - 1, '" name="categoryIcon"><input type="hidden" id="vdt', $i - 1, '" name="createdAt">', chr(10);
  echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<div>
<button id="submitButton" dojoType="dijit.form.Button" type="submit" name="submitButton" onclick="salvarVenues()" style="float: left; padding-right: 3px; margin-left: 0px; margin-bottom: 15px" disabled>Salvar</button>
<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="cancelButton" style="float: left; padding-right: 3px;">Cancelar</button>
<div id="dropdownButtonContainer" style="float: left"></div>
</div>
<div data-dojo-type="dijit.Dialog" id="dlg_cats" data-dojo-props='title:"Categorias"'>
<div id="catsContainer"></div>
<div id="treeContainer"></div>
<button id="saveCatsButton" dojoType="dijit.form.Button" type="button" onclick="salvarCategorias()" name="saveCatsButton">Confirmar</button>
<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){dijit.byId('dlg_cats').hide();}">Cancelar</button>
<br><div id="venueIndex" style="display: none"></div><div id="catsIds" style="display: none"></div><div id="catsIcones" style="display: none"></div>
</div>
</body>
</html>
