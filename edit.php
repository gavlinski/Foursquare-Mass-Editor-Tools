<?php
mb_internal_encoding("UTF-8");
mb_http_output("iso-8859-1");
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

define("VERSION", "List Venues Editor 0.9");
define("TEMPLATE1", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/><script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script><script type="text/javascript">dojo.require("dijit.form.Button");</script><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body class="claro">');
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
      if (($l == 53) or ($l == 54))
        $ret[] = substr($line, -$l + 26);
      else {
        $ret = explode('venuesArray.push(venue', $line);
        $ret = array_slice($ret, 1);
      }
    }
  }

  if (count($ret) > 0) {
    foreach ($ret as &$r) {
      $venues[$i] = substr($r, 0, 24);
      $r = str_pad($venues[$i], 53, "https://foursquare.com/venue/", STR_PAD_LEFT);
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
        $line = str_pad($line, 54, "https://foursquare.com/venue/", STR_PAD_LEFT);
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
  $lines = file($html);
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

  /*** Paginas normais com a tag <a href="https://foursquare.com/venue/..."></a> ***/
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
      if (stripos($tag->getAttribute('href'), "/venue/") !== false ) {
        $venues[$i] = substr($tag->getAttribute('href'), 7, 24);
        $ret[$i] = str_pad($tag->getAttribute('href'), 53, "https://foursquare.com", STR_PAD_LEFT);
        $i++;
      }
    }
  } else {
    foreach ($ret as &$r) {
      $venues[$i] = substr($r, 0, 24);
      $r = str_pad($venues[$i], 53, "https://foursquare.com/venue/", STR_PAD_LEFT);
      $i++;
    }
    /*** break the reference with the last element ***/
    unset($r);
  }

  //print_r($file);
  //echo '<br><br>';
  //print_r($venues);
  //exit;
  return $ret;
}

if ((isset($arquivo)) && (is_uploaded_file($arquivo))) {
  $campos = $_POST["campos"];
  $file = validarVenues(file($arquivo));

} else if ((isset($pagina)) && ($pagina != "")) {
  $campos = $_POST["campos2"];
  $file = parseVenues($pagina);
  if (sizeof($file) == 0) {
    echo ERRO03;
    exit;
  }

} else if (isset($lista)) {
  $campos = $_POST["campos3"];
  $file = validarVenues($lista);

} else {
  echo ERRO99;
  exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
<title><?php echo VERSION;?></title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">
dojo.require("dijit.Dialog");
dojo.require("dijit.form.Button");
dojo.require("dijit.form.ComboBox");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.Tooltip");
dojo.require("dojo.data.ItemFileReadStore");
dojo.require("dijit.Tree");
var total = 0;
var venues = "";
var categorias = new Array();
var store = {};
var timer = null;
function xmlhttpRequest(metodo, endpoint, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        if (metodo == "POST")
          document.getElementById("result" + i).innerHTML = "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>";
        else if ((metodo == "GET") && (resposta.response.categories == undefined))
          atualizarTabela(resposta, i);
        else if (resposta.response.categories != undefined)
          montarArvore(resposta);
      else if (xmlhttp.status == 400)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
      else if (xmlhttp.status == 401)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail + "'>";
      else if (xmlhttp.status == 403)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 403: Forbidden" + "'>";
      else if (xmlhttp.status == 404)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 404: Not Found" + "'>";
      else if (xmlhttp.status == 405)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 405: Method Not Allowed" + "'>";
      else if (xmlhttp.status == 409)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 409: Conflict" + "'>";
      else if (xmlhttp.status == 500)
        document.getElementById("result" + i).innerHTML = "<img src='img/erro.png' alt='" + "Erro 500: Internal Server Error" + "'>";
      else
        document.getElementById(result).innerHTML = "<img src='img/erro.png' alt='" + "Erro desconhecido: " + xmlhttp.status + "'>";
    }
  }
  xmlhttp.open(metodo, endpoint, true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(dados);
  return false;
}
function Categoria(ids, nomes, icones) {
   this.ids = ids;
   this.nomes = nomes;
   this.icones = icones;
}
function atualizarCategorias(nomes, ids, icones) {
  document.getElementById("catsContainer").innerHTML = "";
  for (j = 0; j < nomes.length; j++)
    document.getElementById("catsContainer").innerHTML += "<div id='categoria" + (j + 1) + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + (j + 1) + "')\" onclick=\"removerCategoria('" + (j + 1) + "')\">" + nomes[j] + ",</div>";
  document.getElementById("catsContainer").innerHTML = document.getElementById("catsContainer").innerHTML.slice(0, -7) + "</div>";
  document.getElementById("catsIds").innerHTML = ids;
  document.getElementById("catsIcones").innerHTML = icones;
  //console.log(document.getElementById("catsIcones").innerHTML);
}
function carregarCategorias(i) {
  var nomes = new Array();
  var ids =  "";
  var icones = "";
  if (document.getElementById("cid" + i).value != "") {
    nomes = document.getElementById("cna" + i).value.split(",", 3);
    ids = document.getElementById("cid" + i).value;
    icones = document.getElementById("cic" + i).value;
  }
  atualizarCategorias(nomes, ids, icones);
  document.getElementById("venueIndex").innerHTML = i;
  dijit.byId("dlg_cats").show();
}
function removerCategoria(i) {
  if (timer)
    clearTimeout(timer);
  timer = setTimeout(function() {
    //console.info('Remover a categoria ' + i);
    var nomes = new Array();
    var ids = "";
    var icones = "";
    if ((document.getElementById("categoria1") !== null) && (i != 1)) {
      nomes.push(document.getElementById("categoria1").innerHTML.replace(/,/gi, ""));
      ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
    }
    if ((document.getElementById("categoria2") !== null) && (i != 2)) {
      nomes.push(document.getElementById("categoria2").innerHTML.replace(/,/gi, ""));
      ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
    }
    if ((document.getElementById("categoria3") !== null) && (i != 3)) {
      nomes.push(document.getElementById("categoria3").innerHTML);
      ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
      icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
    }
    atualizarCategorias(nomes, ids.slice(0, -1), icones.slice(0, -1));
  }, 250);
}
function tornarCategoriaPrimaria(i) {
  clearTimeout(timer);
  //console.info("Tornar a categoria " + i + " primaria");
  var nomes = new Array();
  var ids = "";
  var icones = "";
  nomes.push(document.getElementById("categoria" + i).innerHTML.replace(/,/gi, ""));
  if (i == 1) {
    ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
  } else if (i == 2) {
    ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
  } else if (i == 3) {
    ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
  }
  if ((document.getElementById("categoria1") !== null) && (i != 1)) {
    nomes.push(document.getElementById("categoria1").innerHTML.replace(/,/gi, ""));
    ids += document.getElementById("catsIds").innerHTML.substr(0, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 1)[0] + ",";
  }
  if ((document.getElementById("categoria2") !== null) && (i != 2)) {
    nomes.push(document.getElementById("categoria2").innerHTML.replace(/,/gi, ""));
    ids += document.getElementById("catsIds").innerHTML.substr(25, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 2)[1] + ",";
  }
  if ((document.getElementById("categoria3") !== null) && (i != 3)) {
    nomes.push(document.getElementById("categoria3").innerHTML);
    ids += document.getElementById("catsIds").innerHTML.substr(50, 24) + ",";
    icones += document.getElementById("catsIcones").innerHTML.split(",", 3)[2] + ",";
  }
  atualizarCategorias(nomes, ids.slice(0, -1), icones.slice(0, -1));
}
function salvarCategorias() {
  var i = document.getElementById("venueIndex").innerHTML;
  var nomes = "";
  if (document.getElementById("catsIds").innerHTML != "") {
    nomes = document.getElementById("categoria1").innerHTML;
    if (document.getElementById("categoria2") !== null)
      nomes += document.getElementById("categoria2").innerHTML;
    if (document.getElementById("categoria3") !== null)
      nomes += document.getElementById("categoria3").innerHTML;
    document.getElementById("cna" + i).value = nomes;
    document.getElementById("cid" + i).value = document.getElementById("catsIds").innerHTML;
    document.getElementById("cic" + i).value = document.getElementById("catsIcones").innerHTML;
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:carregarCategorias(" + i + ")'><img id=catImg" + i + " src='" + document.getElementById("cic" + i).value.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
    //console.log(document.getElementById("cna" + i).value);
    //console.log(document.getElementById("cid" + i).value);
    //console.log(document.getElementById("cic" + i).value);
    dijit.byId('dlg_cats').hide();
  }
}
function atualizarTabela(resposta, i) {
  total++;
  var linha = "";
  categorias[i] = new Categoria();
  for (j = 0; j < resposta.response.venue.categories.length; j++) {
    categorias[i].ids += resposta.response.venue.categories[j].id + ",";
    categorias[i].nomes += resposta.response.venue.categories[j].name + ",";
    categorias[i].icones += resposta.response.venue.categories[j].icon.prefix + resposta.response.venue.categories[j].icon.sizes[0] + resposta.response.venue.categories[j].icon.name + ",";
  }
  if (categorias[i].ids != undefined) {
    categorias[i].ids = categorias[i].ids.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cid" + i).value = categorias[i].ids;
    categorias[i].nomes = categorias[i].nomes.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cna" + i).value = categorias[i].nomes;
    categorias[i].icones = categorias[i].icones.slice(0, -1).replace(/undefined/gi, "");
    document.getElementById("cic" + i).value = categorias[i].icones;
    //console.log(document.getElementById("cna" + i).value + " (" + document.getElementById("cid" + i).value + ") [" + document.getElementById("cic" + i).value + "]");
    //console.log(categorias[i].nomes + " (" + categorias[i].ids + ") [" + categorias[i].icones + "]");
  }
  for (j = 1; j < document.forms[i].elements.length - 2; j++) {
    switch (document.forms[i].elements[j].name) {
    case "name":
      document.forms[i]["name"].value = resposta.response.venue.name;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'name;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.name + '";';
      break;
    case "address":
      document.forms[i]["address"].value = resposta.response.venue.location.address;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'address;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.address + '";';
      break;
    case "crossStreet":
      document.forms[i]["crossStreet"].value = resposta.response.venue.location.crossStreet;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'crossStreet;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.crossStreet + '";';
      break;
    case "city":
      document.forms[i]["city"].value = resposta.response.venue.location.city;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'city;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.city + '";';
      break;
    case "state":
      document.forms[i]["state"].value = resposta.response.venue.location.state;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'state;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.state + '";';
      break;
    case "zip":
      document.forms[i]["zip"].value = resposta.response.venue.location.postalCode;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'zip;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.postalCode + '";';
      break;
    case "twitter":
      document.forms[i]["twitter"].value = resposta.response.venue.contact.twitter;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'twitter;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.contact.twitter + '";';
      break;
    case "phone":
      document.forms[i]["phone"].value = resposta.response.venue.contact.phone;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'phone;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.contact.phone + '";';
      break;
    case "url":
      document.forms[i]["url"].value = resposta.response.venue.url;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'url;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.url + '";';
      break;
    case "description":
      document.forms[i]["description"].value = resposta.response.venue.description;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'description;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.description + '";';
      break;
    case "ll":
      document.forms[i]["ll"].value = resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng;
      if (total == 1) {
        if (j == 1)
          venues = 'venue;categoryId;';
        venues = venues + 'll;';
      }
      if (j == 1)
        linha = '"' + resposta.response.venue.id + '";"' + categorias[i].ids + '";';
      linha = linha + '"' + resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng + '";';
      break;
    default:
      break;
    }
    if (document.forms[i].elements[j].value == "undefined") {
      document.forms[i].elements[j].value = "";
      var x = window.scrollX, y = window.scrollY;
      document.forms[i].elements[j].focus();
      document.forms[i].elements[j].blur();
      window.scrollTo(x, y);
    }
    document.getElementById("result" + i).innerHTML = "";
    //if (total == document.forms.length) {
      //dojo.byId("regras").focus();
      //dojo.byId("regras").blur();
    //}
  }
  if (total == 1)
    venues = venues.slice(0, -1) + '\n';
  venues = venues + linha.replace(/undefined/gi, "") + '\n';
  if (resposta.response.venue.categories[0] == undefined)
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:carregarCategorias(" + i + ")'><img id=catImg" + i + " src='http://foursquare.com/img/categories/none.png' style='height: 22px; width: 22px; margin-left: 0px'></a>";
  else
    document.getElementById("icone" + i).innerHTML = "<a id='catLnk" + i + "' href='javascript:carregarCategorias(" + i + ")'><img id=catImg" + i + " src='" + categorias[i].icones.split(",", 1)[0] + "' style='height: 22px; width: 22px; margin-left: 0px'></a>";
  var dicaVenue = "<b>" + resposta.response.venue.name + "</b>";
  try {
    if (document.forms[i]["address"].value != "")
      dicaVenue += "<br>" + document.forms[i]["address"].value;
  } catch(err) { }
  try {
    if (document.forms[i]["crossStreet"].value != "")
      dicaVenue += " (" + document.forms[i]["crossStreet"].value + ")";
  } catch(err) { }
  try {
    if (document.forms[i]["city"].value != "") {
      dicaVenue += "<br>" + document.forms[i]["city"].value;
      if (document.forms[i]["state"].value != "") {
        dicaVenue += ", " + document.forms[i]["state"].value;
        if (document.forms[i]["zip"].value != "")
          dicaVenue += " " + document.forms[i]["zip"].value;
      }
    } else if (document.forms[i]["state"].value != "") {
      dicaVenue += document.forms[i]["state"].value;
      if (document.forms[i]["zip"].value != "")
        dicaVenue += " " + document.forms[i]["zip"].value;
    } else if (document.forms[i]["zip"].value != "") {
      dicaVenue += document.forms[i]["zip"].value;
    }
  } catch(err) { }
  new dijit.Tooltip({
    connectId: ["v" + i],
    label: dicaVenue
  });
}
function montarArvore(resposta) {
  var restructuredData = dojo.map(resposta.response.categories, dojo.hitch(this, function(category1) {
    var newCategory1 = {};
    newCategory1.id = category1.id;
    newCategory1.name = category1.name;
    newCategory1.icon = category1.icon.prefix + category1.icon.sizes[0] + category1.icon.name;
    newCategory1.children = dojo.map(category1.categories, dojo.hitch(this, function(idPrefix, category2) {
      var newCategory2 = {};
      //newCategory2.id = idPrefix + "_" + category2.id;
      newCategory2.id = category2.id;
      newCategory2.name = category2.name;
      newCategory2.icon = category2.icon.prefix + category2.icon.sizes[0] + category2.icon.name;
      if (category2.categories != "") {
        newCategory2.children = dojo.map(category2.categories, dojo.hitch(this, function(idPrefix, category3) {
          var newCategory3 = {};
          //newCategory3.id = idPrefix + "_" + category3.id;
          newCategory3.id = category3.id;
          newCategory3.name = category3.name;
          newCategory3.icon = category3.icon.prefix + category3.icon.sizes[0] + category3.icon.name;
          return newCategory3;
        }, newCategory2.id));
      }
    return newCategory2;
    }, newCategory1.id));
  return newCategory1;
  }));
  //JSONText = JSON.stringify(restructuredData);
  //console.log(JSONText);
  store = new dojo.data.ItemFileReadStore({
    data: {
      "identifier": "id",
      "label": "name",
      "items": restructuredData
    }
  });
  var treeModel = new dijit.tree.ForestStoreModel({
    store: store,
    rootId: "root",
    rootLabel: "Categorias",
    childrenAttrs: ["children"]
  });
  new dijit.Tree({
    model: treeModel,
    showRoot: false,
    onClick: treeOnClick,
    getIconClass: function(/*dojo.data.Item*/ item, /*Boolean*/ opened) {
      var style = document.createElement('style');
      style.type = 'text/css';
      style.innerHTML = '.icon' + item.id + ' { background-image: url(\'' + item.icon + '\'); background-size: 16px 16px; width: 16px; height: 16px; }';
      document.getElementsByTagName('head')[0].appendChild(style);
      return 'icon' + item.id;
    }
  }, "treeContainer");
}
function treeOnClick(item) {
  if (!item.root) {
    //console.log("Execute of node " + store.getLabel(item) + ", id=" + store.getValue(item, "id") + ", icon=" + store.getValue(item, "icon"));
    var i = 1;
    if (document.getElementById("categoria3") !== null)
      //console.warn("Limite maximo de categorias");
      return false;
    else if (((document.getElementById("categoria2") !== null) && (document.getElementById("categoria2").innerHTML.replace(/,/gi, "") == store.getLabel(item))) || ((document.getElementById("categoria1") !== null) && (document.getElementById("categoria1").innerHTML.replace(/,/gi, "") == store.getLabel(item))))
      //console.warn("Categoria repetida");
      return false;
    else if (document.getElementById("categoria2") !== null)
      i = 3;
    else if (document.getElementById("categoria1") !== null)
      i = 2;
    // Adiciona categoria
    if (i != 1) {
      document.getElementById("catsContainer").innerHTML = document.getElementById("catsContainer").innerHTML.slice(0, -6) + ",</div>"
      document.getElementById("catsIds").innerHTML += ",";
      document.getElementById("catsIcones").innerHTML += ",";
    }
    document.getElementById("catsContainer").innerHTML += "<div id='categoria" + i + "' class='categoria' ondblclick=\"tornarCategoriaPrimaria('" + i + "')\" onclick=\"removerCategoria('" + i + "')\">" + store.getLabel(item) + "</div>";
    document.getElementById("catsIds").innerHTML += store.getValue(item, "id");
    document.getElementById("catsIcones").innerHTML += store.getValue(item, "icon");
    return true;
  }
}
var oauth_token = "<?php echo $oauth_token;?>";
function carregarVenues() {
  var venue;
  //console.info("Recuperando dados das venues...");
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=20120311", null, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Recuperando dados...'>";
  }
  //console.info("Venues recuperadas!");
}
function salvarVenues() {
  var venue, dados, ll;
  //console.info("Enviando dados...");
  for (i = 0; i < document.forms.length; i++) {
    dados = "oauth_token=" + oauth_token;
    for (j = 1; j < document.forms[i].elements.length; j++) {
      venue = document.forms[i]["venue"].value;
      if ((document.forms[i].elements[j].name != "ll") &&
          (document.forms[i].elements[j].name != "categoryId") &&
          ((document.forms[i].elements[j].name == "name")
           || (document.forms[i].elements[j].name == "address")
           || (document.forms[i].elements[j].name == "crossStreet")
           || (document.forms[i].elements[j].name == "city")
           || (document.forms[i].elements[j].name == "state")
           || (document.forms[i].elements[j].name == "zip")
           || (document.forms[i].elements[j].name == "twitter")
           || (document.forms[i].elements[j].name == "phone")
           || (document.forms[i].elements[j].name == "url")
           || (document.forms[i].elements[j].name == "description")))
        dados += "&" + document.forms[i].elements[j].name + "=" + document.forms[i].elements[j].value.replace(/&/g, "%26");
      else if (document.forms[i].elements[j].name == "categoryId") {
        categoryId = document.forms[i]["categoryId"].value;
        if (categoryId != null && categoryId != "")
          dados += "&categoryId=" + document.forms[i]["categoryId"].value;
      } else if (document.forms[i].elements[j].name == "ll") {
        ll = document.forms[i]["ll"].value;
        if (ll != null && ll != "")
          dados += "&ll=" + document.forms[i]["ll"].value;
      }
    }
    dados += "&v=20120311";
    //console.group("venue=" + venue + " (" + i + ")");
    //console.log(dados);
    //console.groupEnd();
    xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //console.info("Dados enviados!");
}
function carregarListaCategorias() {
  //console.info("Recuperando dados das categorias...");
  xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/categories" + "?oauth_token=" + oauth_token + "&v=20120221", null, i);
  //console.info("Categorias recuperadas!");
}
var dlgGuia;
dojo.addOnLoad(function() {
  // create the dialog:
  dlg_guia = new dijit.Dialog({
    title: "Guia de estilo",
    style: "width: 435px"
  });
  carregarVenues();
  carregarListaCategorias();
});
function showDialog_guia() {
  // set the content of the dialog:
  dlg_guia.attr("content", "<ul><li><p>Use sempre a ortografia e as letras maiúsculas corretas.</p></li><li><p>Em redes ou lugares com vários locais, não é mais preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Starbucks&quot; ou &quot;Apple Store&quot; (em vez de &quot;Starbucks - Queen Anne&quot; ou &quot;Apple Store - Cidade alta&quot;).</p></li><li><p>Sempre que possível, use abreviações: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc.</p></li><li>Cross Street should be like one of the following:<ul><li>na R. Main (para lugares em uma esquina)</li><li>entre a Av. 2a. e Av. 3a. (para lugares no meio de um quarteirão)</li></ul><br></li><li>A R. Cross não deve ter o nome repetido da rua no endereço.<ul><li>Se o local é na R. Principal, a rua transversal deve ser &quot;na Segunda Av.&quot;</li><li>A transversal não deve ser &quot;R. Principal na R. Segunda&quot;</li></ul></li><li><p>Os nomes de Estados e províncias devem ser abreviados.</p></li><li><p>Em caso de dúvida, formate os endereços de lugares de acordo com as diretrizes postais locais.</p></li><li><p>Se tiver mais perguntas sobre a criação e edição de lugares no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre lugares</a>.</p></li></ul>");
  dlg_guia.show();
}
//var node = dojo.byId("forms");
//dojo.connect(node, "onkeypress", function(e) {
  //if (e.keyCode == dojo.keys.DOWN_ARROW) {
    //document.forms[1].elements[1].focus();
    //dojo.stopEvent(e);
  //}
//});
function exportarVenues() {
  window.location.href = "data:application/csv;charset=iso-8859-1," + encodeURIComponent(venues);
}
</script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
</head>
<body class="claro">
<h2>Editar venues!</h2>
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
  echo '<input type="hidden" name="venue" value="', $venue, '"><a id="v', $i - 1, '" href="', $f, '" target="_blank" style="margin-right: 5px;">';
  if (count($file) < 10)
    echo $i;
  else if (count($file) < 100)
    echo str_pad($i, 2, "0", STR_PAD_LEFT);
  else
    echo str_pad($i, 3, "0", STR_PAD_LEFT);
  echo '</a>', chr(10);

  echo '<span id="icone', $i - 1, '"><img id=catImg', $i, ' src="http://foursquare.com/img/categories/none.png" style="height: 22px; width: 22px; margin-left: 0px"></span>', chr(10);

  if ($editName) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value=" " placeHolder="Nome" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editAddress) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value=" " placeHolder="Endere&ccedil;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editCross) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value=" " placeHolder="Rua Cross" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editCity) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value=" " placeHolder="Cidade" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editState) {
    echo '<select dojoType="dijit.form.ComboBox" name="state" style="width: 4em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'"><option value=""></option>';
    for ($j = 0; $j <= 26; $j++) {
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo '</select>', chr(10);
  }

  if ($editZip) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value=" " placeHolder="CEP" style="width: 6em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editTwitter) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value=" " placeHolder="Twitter" style="width: ', 8 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editPhone) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value=" " placeHolder="Telefone" style="width: 8em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editUrl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value=" " placeHolder="Website" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editDesc) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value=" " placeHolder="Descri&ccedil;&atilde;o" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  if ($editLl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" maxlength="402" value=" " placeHolder="Lat/Long" style="width: ', 9 + $ajusteInput, 'em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }
  
  echo '<input id="cid', $i - 1, '" type="hidden" name="categoryId"><input id="cna', $i - 1, '" type="hidden" name="categoryName"><input id="cic', $i - 1, '" type="hidden" name="categoryIcon">', chr(10);
  echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<button id="submitButton" dojoType="dijit.form.Button" type="submit" name="submitButton" onclick="salvarVenues()">Salvar</button>
<button id="exportButton" dojoType="dijit.form.Button" type="button" onclick="exportarVenues()" name="exportButton">Exportar</button>
<button id="backButton" dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="backButton">Voltar</button>
<br><br>
<div data-dojo-type="dijit.Dialog" id="dlg_cats" data-dojo-props='title:"Categorias"'>
<div id="catsContainer"></div>
<div id="treeContainer"></div>
<button id="saveCatsButton" dojoType="dijit.form.Button" type="button" onclick="salvarCategorias()" name="saveCatsButton">Salvar</button>
<button data-dojo-type="dijit.form.Button" type="button" data-dojo-props="onClick:function(){dijit.byId('dlg_cats').hide();}">Cancelar</button>
<br><div id="venueIndex" style="display: none"></div><div id="catsIds" style="display: none"></div><div id="catsIcones" style="display: none"></div>
</div>
</body>
</html>
