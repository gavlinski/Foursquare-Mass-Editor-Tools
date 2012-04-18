<?php

/**
 * CSV Venues Editor
 *
 * Edita venues de acordo com os dados recebidos do load_csv.php
 *
 * @category   Foursquare
 * @package    Foursquare-Mass-Editor-Tools
 * @author     Elio Gavlinski <gavlinski@gmail.com>
 * @copyright  Copyleft (c) 2011-2012
 * @version    1.1
 * @link       https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/edit_csv.php
 * @since      File available since Release 0.3
 */

session_start();
if (isset($_SESSION["oauth_token"])) {
	$oauth_token = $_SESSION["oauth_token"];
	$file = $_SESSION["file"];
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
<script type="text/javascript" src="js/4sq_csv.js"></script>
</head>
<body class="claro">
<h2>Editar venues</h2>
<p>Antes de salvar suas altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a href="javascript:showDialog_guia();">guia de estilo</a> e as <a href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.</p>
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

$ufs = array("AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO");

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
    echo '<span id="icone', $i - 1, '"><img id=catImg', $i, ' src="http://foursquare.com/img/categories/none.png" style="height: 22px; width: 22px; margin-left: 0px"></span>', chr(10);
  }

  $name = htmlentities($f['name']);
  if ($hasName) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value="', $name, '" placeHolder="Nome" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $address = htmlentities($f['address']);
  if ($hasAddress) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value="', $address, '" placeHolder="Endere&ccedil;o" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $crossStreet = htmlentities($f['crossStreet']);
  if ($hasCross) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value="', $crossStreet, '" placeHolder="Rua Cross" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $city = htmlentities($f['city']);
  if ($hasCity) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value="', $city, '" placeHolder="Cidade" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $state = $f['state'];
  if ($hasState) {
    echo '<select dojoType="dijit.form.ComboBox" name="state" style="width: 4em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
    $key = array_search($state, $ufs);
    for ($j = 0; $j <= 26; $j++) {
      if ($key == $j) {
        echo '<option value="', $state, '" selected>', $state, '</option>';
      }
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo chr(10), '</select>', chr(10);
  }

  $zip = $f['zip'];
  if ($hasZip) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value="', $zip, '" placeHolder="CEP" style="width: 6em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $twitter = $f['twitter'];
  if ($hasTwitter) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value="', $twitter, '" placeHolder="Twitter" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $phone = $f['phone'];
  if ($hasPhone) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value="', $phone, '" placeHolder="Telefone" style="width: 8em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $url = $f['url'];
  if ($hasUrl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value="', $url, '" placeHolder="Website" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $description = htmlentities($f['description']);
  if ($hasDesc) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value="', $description, '" placeHolder="Descri&ccedil;&atilde;o" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  $ll = $f['ll'];
  if ($hasLl) {
    //if (($ll != '') && ($ll != ' ')) {
      echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" maxlength="402" value="', $ll, '" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
    //} else {
      //echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
    //}
  }

  $categoryId = htmlentities($f['categoryId']);
  if ($hasCategoryId) {
    //if (($categoryId != '') && ($categoryId != ' ')) {
      echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" maxlength="75" value="', $categoryId, '" placeHolder="Categorias" style="width: 9em; margin-left: 5px;" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
    //} else {
      //echo '<input type="text" dojoType="dijit.form.TextBox" name="categoryId" placeHolder="Categorias" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
    //}
  }

  echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<div>
<button id="submitButton" dojoType="dijit.form.Button" type="submit" name="submitButton" onclick="salvarVenues()" style="float: left; padding-right: 3px; margin-left: 0px; margin-bottom: 15px">Salvar</button>
<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="cancelButton" style="float: left">Cancelar</button>
</div>
</body>
</html>
