<?php
mb_internal_encoding("UTF-8");
mb_http_output("iso-8859-1");
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1", true);

define("VERSION", "List Venues Editor 1.0");
define("TEMPLATE1", '<html><head><title>Superuser Tools - foursquare</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/><script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script><script type="text/javascript">dojo.require("dijit.form.Button");</script><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body class="claro">');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)">Voltar</button></p></body></html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas e tente novamente.' . TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>Erro na leitura do endere&ccedil;os de uma das venues!</p><p>Verifique o arquivo ou a lista e tente novamente.' . TEMPLATE2);
define("ERRO03", TEMPLATE1 . '<p>Nenhuma venue encontrada no endere&ccedil;o especificado.</p><p>Verifique a p&aacute;gina e tente novamente.' . TEMPLATE2);
define("ERRO99", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body><p>Erro na leitura dos dados!</body></html>');

$oauth_token = $_POST["oauth_token"];

if (isset($_FILES['txts']['tmp_name'][0]))
  $arquivo = $_FILES['txts']['tmp_name'][0];
if (isset($_POST["pagina"]))
  $pagina = $_POST["pagina"];
if ((isset($_POST["textarea"])) && ($_POST["textarea"] != ""))
  $lista = explode("\n", $_POST["textarea"]);

function filtrarArray($array) {
  foreach ($array as $i => &$value) {
    $value = trim($value);
    if (strlen($value) < 24)
      unset($array[$i]);
  }
  unset($value);
  return array_values(array_unique($array));
}

function validarVenues($lines) {
  $ret = array();
  global $venues;
  $venues = array();
  $i = 0;

  foreach ($lines as $line_num => $line) {
    /*** Places ***/
    $pos = stripos($line, ' "id": "');
    if ($pos !== false) {
      //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . " at position <b>" . $pos . "</b><br />\n";
      $ret[] = substr($line, $pos + 8);

    /*** Tidysquare ***/
    } else if (stripos($line, 'venuesArray.push(venue') !== false) {
      $l = strlen($line);
      //echo "Line #<b>{$line_num}</b> : " . htmlspecialchars($line) . "l : " . $l . "<br />\n";
      //if (($l == 53) or ($l == 54))
      if ($l == 48)
        //$ret[] = substr($line, -$l + 26);
        $ret[] = substr($line, -26);
      else {
        $ret = explode('venuesArray.push(venue', $line);
        $ret = array_slice($ret, 1);
      }
    }
  }

  if (count($ret) > 0) {
    foreach ($ret as &$r) {
      $venues[$i] = substr($r, 0, 24);
      $r = "https://foursquare.com/v/" . $venues[$i];
      $i++;
    }
    /*** break the reference with the last element ***/
    unset($r);
    //print_r($ret);
    //echo '<br><br>';
    //print_r($venues);
    //exit;
    return $ret;
  } else if (count($lines) > 500) {
    echo ERRO01;
    exit;
  } else {
    foreach ($lines as &$line) {
      $line = trim($line);
      if ((stripos($line, "foursquare.com/v") === false) && (strlen($line) > 25)) {
        echo ERRO02;
        exit;
      }
      if (strlen($line) > 25) {
        $l = strlen($line) - 2;
        if ($line[$l] === "/")
          $line = substr($line, 0, $l);
        $line = str_replace("/edit", "", $line);
        $venues[$i] = substr($line, strrpos($line, "/") + 1, 24);
      } else {
        $venues[$i] = $line;
        $line = "https://foursquare.com/v/" . $line;
        //$line = "" + $venues[$i];
      }
      $i++;
    }
    /*** break the reference with the last element ***/
    unset($line);
    //print_r($lines);
    //echo '<br><br>';
    //print_r($venues);
    //exit;
    return $lines;
  }
}

function parseVenues($html) {
  try {
    $lines = file($html);
  } catch (Exception $e) {
    echo "Exceção pega: ",  $e->getMessage(), "\n";
  } 
  $ret = array();
  global $venues;
  $venues = array();
  $i = 0;

  foreach ($lines as $line_num => $line) {
    /*** Listas do usuario do foursquare ***/
    //if (stripos($line, 'ITEMS_JSON') !== false) {
    if (stripos($line, 'itemsJson') !== false) {
      //$ret = array_slice(explode('\"venue\":{\"id\":\"', $line), 1);
      $ret = array_slice(explode('"venue":{"id":"', $line), 1);
      break;
    /*** Resultados da pesquisa ***/
    } else if (stripos($line, 'fourSq.tiplists.setupSearchPageListControls([{"id":"v') !== false) {
      $ret = array_slice(explode('"id":"v', $line), 1);
      break;
    }
  }

  /*** Paginas normais com a tag <a href="https://foursquare.com/venue/..."></a> ou <a href="https://foursquare.com/v/..."></a> ***/
  if ($ret == null) {
    /*** a new dom object ***/
    $dom = new domDocument;

    /*** get the HTML (suppress errors) ***/
    @$dom->loadHTML(file_get_contents($html));

    /*** remove silly white space ***/
    $dom->preserveWhiteSpace = false;

    /*** get the links from the HTML ***/
    $links = $dom->getElementsByTagName('a');
    
    /*** loop over the links ***/
    foreach ($links as $tag) {
      if ((stripos($tag->getAttribute('href'), "/venue/") !== false) || (stripos($tag->getAttribute('href'), "/v/") !== false)) {
        $venues[$i] = substr($tag->getAttribute('href'), -24);
        $ret[$i] = "https://foursquare.com" . $tag->getAttribute('href');
        $i++;
      }
    }
  } else {
    foreach ($ret as &$r) {
      $venues[$i] = substr($r, 0, 24);
      $r = "https://foursquare.com/v/" . $venues[$i];
      $i++;
    }
    /*** break the reference with the last element ***/
    unset($r);
  }
  $venues = array_values(array_unique($venues));
  return array_values(array_unique($ret));
}

if ((isset($arquivo)) && (is_uploaded_file($arquivo))) {
  $campos = $_POST["campos"];
  $file = validarVenues(filtrarArray(file($arquivo)));

} else if ((isset($pagina)) && ($pagina != "")) {
  $campos = $_POST["campos2"];
  $file = parseVenues(trim($pagina));
  if (sizeof($file) == 0) {
    echo ERRO03;
    exit;
  }

} else if (isset($lista)) {
  $campos = $_POST["campos3"];
  $file = validarVenues(filtrarArray($lista));

} else {
  echo ERRO99;
  exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
<title>foursquare</title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">var oauth_token = "<?= $oauth_token ?>";</script>
<script type="text/javascript" src="js/4sq.js"></script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
</head>
<body class="claro">
<h2>Editar venues</h2>
<p>Antes de salvar suas altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialog_guia();">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.<p>
<div id="listContainer">
<?php
$totalCampos = 0;

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
    echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value=" " placeHolder="Nome" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  } else {
    echo '<input type="hidden" name="name">', chr(10);
  }

  if ($editAddress) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value=" " placeHolder="Endere&ccedil;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editCross) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value=" " placeHolder="Rua Cross" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editCity) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value=" " placeHolder="Cidade" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editState) {
    echo '<select dojoType="dijit.form.ComboBox" name="state" style="width: 4em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')"><option value=""></option>';
    for ($j = 0; $j <= 26; $j++) {
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo '</select>', chr(10);
  }

  if ($editZip) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value=" " placeHolder="CEP" style="width: 6em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editTwitter) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value=" " placeHolder="Twitter" style="width: ', 7 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editPhone) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value=" " placeHolder="Telefone" style="width: 8em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editUrl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value=" " placeHolder="Website" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editDesc) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value=" " placeHolder="Descri&ccedil;&atilde;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }

  if ($editLl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" maxlength="402" value=" " placeHolder="Lat/Long" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onfocus="this.oldvalue = this.value" onchange="verificarAlteracao(this, ', $i - 1, ')">', chr(10);
  }
  
  echo '<input type="hidden" id="cid', $i - 1, '" name="categoryId"><input type="hidden" id="cna', $i - 1, '" name="categoryName"><input type="hidden" id="cic', $i - 1, '" name="categoryIcon"><input type="hidden" id="vdt', $i - 1, '" name="createdAt">', chr(10);
  echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<div>
<button id="submitButton" dojoType="dijit.form.Button" type="submit" name="submitButton" onclick="salvarVenues()" style="float: left; padding-right: 3px; margin-left: 0px;">Salvar</button>
<button id="cancelButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="cancelButton" style="float: left; padding-right: 3px;">Cancelar</button>
<div id="dropdownButtonContainer" style="float: left; margin-bottom: 15px"></div>
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
