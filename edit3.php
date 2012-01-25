<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

define("VERSION", "List Venues Editor 0.3 beta");
define("TEMPLATE1", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/><script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script><script type="text/javascript">dojo.require("dijit.form.Button");</script><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body class="claro">');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)">Voltar</button></p></body></html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas do arquivo e tente novamente.' . TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>Erro na leitura do endere&ccedil;o das venues!</p><p>Verifique o arquivo e tente novamente.' . TEMPLATE2);
define("ERRO03", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas e tente novamente.' . TEMPLATE2);
define("ERRO04", TEMPLATE1 . '<p>Erro na leitura do endere&ccedil;o das venues!</p><p>Verifique a lista e tente novamente.' . TEMPLATE2);
define("ERRO99", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body><p>Erro na leitura dos dados!</body></html>');

$oauth_token = $_POST["oauth_token"];

$txt = $_FILES['txts']['tmp_name'][0];
$pagina = $_POST["pagina"];
$lista = explode("\n", $_POST["textarea"]);

function validar ($file) {
  //print_r($file);
  if (count($file) > 500) {
    return 1;
    exit;
  }
  global $venues;
  $venues = array();
  $i = 0;
  //echo '<br><br>';
  //print_r($file);
  //exit;
  foreach ($file as &$f) {
    if ((stripos($f, "foursquare.com/v") === false) && (strlen($f) > 25)) {
      return 2;
      exit;
    }
    if (strlen($f) > 25) {
      $l = strlen($f) - 2;
      if ($f[$l] === "/")
        $f = substr($f, 0, $l);
      $f = str_replace("/edit", "", $f);
      $venues[$i] = substr($f, strrpos($f, "/") + 1, 24);
    } else {
      $venues[$i] = $f;
      $f = str_pad($f, 54, "https://foursquare.com/venue/", STR_PAD_LEFT);
      //$f = "" + $venues[$i];
    }
    $i++;
  }
  unset($f); // break the reference with the last element
  //echo '<br><br>';
  //print_r($file);
  //echo '<br><br>';
  //print_r($venues);
  //exit;
  return 0;
}

function getLinks($link) {
  /*** return array ***/
  $ret = array();

  /*** a new dom object ***/
  $dom = new domDocument;

  /*** get the HTML (suppress errors) ***/
  @$dom->loadHTML(file_get_contents($link));

  /*** remove silly white space ***/
  $dom->preserveWhiteSpace = false;

  /*** get the links from the HTML ***/
  $links = $dom->getElementsByTagName('a');
    
  /*** loop over the links ***/
  foreach ($links as $tag) {
    $ret[$tag->getAttribute('href')] = $tag->childNodes->item(0)->nodeValue;
  }
  return $ret;
}

if (is_uploaded_file($txt)) {
  $file = file($txt);
  $campos = $_POST["campos"];
  $result = validar($file);
  if ($result != 0) {
    switch ($result) {
      case 1:
        echo ERRO01;
        break;
      case 2:
        echo ERRO02;
        break;
    }
    exit;
  }
} else if ($pagina != "")  {
  /*** get the links ***/
  $urls = getLinks($pagina);
  $campos = $_POST["campos2"];

  /*** check for results ***/
  if (sizeof($urls) > 0) {
    $file = array();
    $i = 0;
    foreach($urls as $key => $value) {
      if (stripos($key, "/venue/") !== false ) {
        $file[$i] = str_pad($key, 53, "https://foursquare.com", STR_PAD_LEFT);
        $i++;
      }
    }
    validar($file);
  } else {
    echo "No links found at $pagina";
  }
} else if ($lista != "")  {
  $file = $lista;
  $campos = $_POST["campos3"];
  $result = validar($file);
  if ($result != 0) {
    switch ($result) {
      case 1:
        echo ERRO03;
        break;
      case 2:
        echo ERRO04;
        break;
    }
    exit;
  }
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
//var total = 0;
function xmlhttpRequest(metodo, endpoint, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        if (metodo == "POST")
          document.getElementById("result" + i).innerHTML = "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>";
        else
          atualizarTabela(resposta, i);
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
function atualizarTabela(resposta, i) {
  //total++;
  for (j = 1; j < document.forms[i].elements.length; j++) {
    switch (document.forms[i].elements[j].name) {
    case "name":
      document.forms[i]["name"].value = resposta.response.venue.name;
      break;
    case "address":
      document.forms[i]["address"].value = resposta.response.venue.location.address;
      break;
    case "crossStreet":
      document.forms[i]["crossStreet"].value = resposta.response.venue.location.crossStreet;
      break;
    case "city":
      document.forms[i]["city"].value = resposta.response.venue.location.city;
      break;
    case "state":
      document.forms[i]["state"].value = resposta.response.venue.location.state;
      break;
    case "zip":
      document.forms[i]["zip"].value = resposta.response.venue.location.postalCode;
      break;
    case "twitter":
      document.forms[i]["twitter"].value = resposta.response.venue.contact.twitter;
      break;
    case "phone":
      document.forms[i]["phone"].value = resposta.response.venue.contact.phone;
      break;
    case "url":
      document.forms[i]["url"].value = resposta.response.venue.url;
      break;
    case "description":
      document.forms[i]["description"].value = resposta.response.venue.description;
      break;
    case "ll":
      document.forms[i]["ll"].value = resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng;
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
  var dica = "<b>" + resposta.response.venue.name + "</b>";
  try {
    if (document.forms[i]["address"].value != "")
      dica += "<br>" + document.forms[i]["address"].value;
  } catch(err) { }
  try {
    if (document.forms[i]["crossStreet"].value != "")
      dica += " (" + document.forms[i]["crossStreet"].value + ")";
  } catch(err) { }
  try {
    if (document.forms[i]["city"].value != "") {
      dica += "<br>" + document.forms[i]["city"].value;
      if (document.forms[i]["state"].value != "") {
        dica += ", " + document.forms[i]["state"].value;
        if (document.forms[i]["zip"].value != "")
          dica += " " + document.forms[i]["zip"].value;
      }
    } else if (document.forms[i]["state"].value != "") {
      dica += document.forms[i]["state"].value;
      if (document.forms[i]["zip"].value != "")
        dica += " " + document.forms[i]["zip"].value;
    } else if (document.forms[i]["zip"].value != "") {
      dica += document.forms[i]["zip"].value;
    }
  } catch(err) { }
  new dijit.Tooltip({
    connectId: ["v" + i],
    label: dica
  });
}
function carregarVenues() {
  var oauth_token = "<?php echo $oauth_token;?>";
  var venue, resposta;
  //document.getElementById("result").innerHTML = "Recuperando dados...";
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=20111223", null, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>"
  }
  //document.getElementById("result").innerHTML = "Dados recuperados!";
}
function salvarVenues() {
  var oauth_token = "<?php echo $oauth_token;?>";
  var venue, dados, ll;
  //document.getElementById("result").innerHTML = "Enviando dados...";
  for (i = 0; i < document.forms.length; i++) {
    dados = "oauth_token=" + oauth_token;
    for (j = 1; j < document.forms[i].elements.length; j++) {
      venue = document.forms[i]["venue"].value;
      if ((document.forms[i].elements[j].name != "ll") &&
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
        dados += "&" + document.forms[i].elements[j].name + "=" + document.forms[i].elements[j].value;
      else if (document.forms[i].elements[j].name == "ll") {
        ll = document.forms[i]["ll"].value;
        if (ll != null && ll != "")
          dados += "&ll=" + document.forms[i]["ll"].value;
      }
    }
    dados += "&v=20111223";
    //document.getElementById("result").innerHTML += "<br>venue=" +venue + "<br>dados=" + dados + "<br>result=result" + i;
    xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
  }
  //document.getElementById("result").innerHTML = "Dados enviados!";
}
var dlg;
dojo.addOnLoad(function() {
  // create the dialog:
  dlg = new dijit.Dialog({
    title: "Guia de estilo",
    style: "width: 400px"
  });
  carregarVenues();
});
function showDialog() {
  // set the content of the dialog:
  dlg.attr("content", "<ul><li><p>Use sempre a ortografia e as letras maiúsculas corretas.</p></li><li><p>Em redes ou lugares com vários locais, não é mais preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Starbucks&quot; ou &quot;Apple Store&quot; (em vez de &quot;Starbucks - Queen Anne&quot; ou &quot;Apple Store – Cidade alta&quot;).</p></li><li><p>Sempre que possível, use abreviações: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc.</p></li><li>Cross Street should be like one of the following:<ul><li>na R. Main (para lugares em uma esquina)</li><li>entre a Av. 2a. e Av. 3a. (para lugares no meio de um quarteirão)</li></ul><br></li><li>A R. Cross não <b>deve</b> ter o nome repetido da rua no endereço.<ul><li>Se o local é na R. Principal, a rua transversal deve ser &quot;na Segunda Av.&quot;</li><li>A transversal não deve ser &quot;R. Principal na R. Segunda&quot;</li></ul></li><li><p>Os nomes de estados e províncias devem ser abreviados.</p></li><li><p><b>Em caso de dúvida, formate os endereços de lugares de acordo com as diretrizes postais locais.</b></p></li><li><p>Se tiver mais perguntas sobre a criação e edição de lugares no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre lugares</a>.</p></li></ul>");
  dlg.show();
}
//var node = dojo.byId("forms");
//dojo.connect(node, "onkeypress", function(e) {
  //if (e.keyCode == dojo.keys.DOWN_ARROW) {
    //document.forms[1].elements[1].focus();
    //dojo.stopEvent(e);
  //}
//});
</script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
</head>
<body class="claro">
<h2>Editar venues!</h2>
<p>Antes de salvar suas altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a id="guia" href="javascript:showDialog();">guia de estilo</a> e as <a id="regras" href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.<p>
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
  echo '<input type="hidden" name="venue" value="', $venue, '"><a id="v', $i - 1, '" href="', $f, '" target="_blank">';
  if (count($file) < 10)
    echo $i;
  else if (count($file) < 100)
    echo str_pad($i, 2, "0", STR_PAD_LEFT);
  else
    echo str_pad($i, 3, "0", STR_PAD_LEFT);
  echo '</a>', chr(10);

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
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value=" " placeHolder="Telefone" style="width: 7em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
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

  echo '<span id="result', $i - 1, '"></span>', chr(10), '</form>', chr(10), '</div>', chr(10);
}
?>
</div>
<button dojoType="dijit.form.Button" type="button" onclick="salvarVenues()" name="submitButton">Salvar</button>
<button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)" name="backButton">Voltar</button>
<p><div id="result"></div></p>
</body>
</html>
