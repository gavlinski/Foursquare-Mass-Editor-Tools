<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

define("VERSION", "CSV Venues Editor 0.5 beta");
define("TEMPLATE1", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1"/><script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script><script type="text/javascript">dojo.require("dijit.form.Button");</script><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body class="claro">');
define("TEMPLATE2", '<p><button dojoType="dijit.form.Button" type="button" onclick="history.go(-1)">Voltar</button></p></body></html>');
define("ERRO01", TEMPLATE1 . '<p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas do arquivo e tente novamente.' . TEMPLATE2);
define("ERRO02", TEMPLATE1 . '<p>A coluna "venue" &eacute; obrigat&oacute;ria para a edi&ccedil;&atilde;o!</p><p>Verifique o arquivo CSV e tente novamente.' . TEMPLATE2);
define("ERRO99", '<html><head><title>' . VERSION . '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><link rel="shortcut icon" href="favicon.ico" type="image/x-icon"/><link rel="stylesheet" type="text/css" href="estilo.css"/></head><body><p>Erro na leitura dos dados!</body></html>');

$oauth_token = $_POST["oauth_token"];
$csv = $_FILES['csvs']['tmp_name'][0];

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
function xmlhttpPost(venue, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        document.getElementById("result" + i).innerHTML = "<img src='img/ok.png' alt='" + xmlhttp.responseText + "' style='vertical-align: middle;'>";
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
  xmlhttp.open("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(dados);
  return false;
}
function salvarVenues() {
  var oauth_token = "<?php echo $oauth_token;?>";
  var venue, dados, ll;
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    dados = "oauth_token=" + oauth_token;
    //var form = dojo.formToObject("form" + (i + 1));
    //document.getElementById("result").innerHTML += dojo.toJson(form, true) + "<br>";
    for (j = 1; j < document.forms[i].elements.length; j++) {
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
    document.getElementById("result" + i).innerHTML = "<img src='img/loading.gif' alt='Enviando dados...'>";
    xmlhttpPost(venue, dados, i);
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
});
function showDialog() {
  // set the content of the dialog:
  dlg.attr("content", "<ul><li><p>Use sempre a ortografia e as letras maiúsculas corretas.</p></li><li><p>Em redes ou lugares com vários locais, não é mais preciso adicionar um sufixo de local. Portanto, pode deixar &quot;Starbucks&quot; ou &quot;Apple Store&quot; (em vez de &quot;Starbucks - Queen Anne&quot; ou &quot;Apple Store – Cidade alta&quot;).</p></li><li><p>Sempre que possível, use abreviações: &quot;Av.&quot; em vez de &quot;Avenida&quot;, &quot;R.&quot; em vez de &quot;Rua&quot;, etc.</p></li><li>Cross Street should be like one of the following:<ul><li>na R. Main (para lugares em uma esquina)</li><li>entre a Av. 2a. e Av. 3a. (para lugares no meio de um quarteirão)</li></ul><br></li><li>A R. Cross não <b>deve</b> ter o nome repetido da rua no endereço.<ul><li>Se o local é na R. Principal, a rua transversal deve ser &quot;na Segunda Av.&quot;</li><li>A transversal não deve ser &quot;R. Principal na R. Segunda&quot;</li></ul></li><li><p>Os nomes de estados e províncias devem ser abreviados.</p></li><li><p><b>Em caso de dúvida, formate os endereços de lugares de acordo com as diretrizes postais locais.</b></p></li><li><p>Se tiver mais perguntas sobre a criação e edição de lugares no foursquare, consulte nossas <a href='https://pt.foursquare.com/info/houserules' target='_blank'>regras da casa</a> e as <a href='http://support.foursquare.com/forums/191151-venue-help' target='_blank'>perguntas frequentes sobre lugares</a>.</p></li></ul>");
  dlg.show();
}
</script>
<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
<link rel="stylesheet" type="text/css" href="estilo.css"/>
</head>
<body class="claro">
<h2>Editar venues!</h2>
<p>Antes de salvar suas altera&ccedil;&otilde;es, n&atilde;o deixe de ler nosso <a href="javascript:showDialog();">guia de estilo</a> e as <a href="https://pt.foursquare.com/info/houserules" target="_blank">regras da casa</a>.<p>
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

$ufs = array("AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO");

$i = 0;

foreach ($file as $f) {
  $i++;

  echo '<div class="row">', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-data" method="post">', chr(10);

  $venue = $f[venue];
  echo '<input type="hidden" name="venue" value="', $venue, '"><a href="https://foursquare.com/v/', $venue, '" target="_blank">';
  if (count($file) < 10)
    echo $i;
  else if (count($file) < 100)
    echo str_pad($i, 2, "0", STR_PAD_LEFT);
  else
    echo str_pad($i, 3, "0", STR_PAD_LEFT);
  echo '</a>', chr(10);

  $name = htmlentities($f[name]);
  if ($hasName) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="name" maxlength="256" value="', $name, '" placeHolder="Nome" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $address = htmlentities($f[address]);
  if ($hasAddress) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="address" maxlength="128" value="', $address, '" placeHolder="Endere&ccedil;o" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $crossStreet = htmlentities($f[crossStreet]);
  if ($hasCross) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="crossStreet" maxlength="51" value="', $crossStreet, '" placeHolder="Rua Cross" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $city = htmlentities($f[city]);
  if ($hasCity) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="city" maxlength="31" value="', $city, '" placeHolder="Cidade" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $state = $f[state];
  if ($hasState) {
    echo '<select dojoType="dijit.form.ComboBox" name="state" style="width: 4em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
    $key = array_search($state, $ufs);
    for ($j = 0; $j <= 26; $j++) {
      if ($key == $j) {
        echo '<option value="', $state, '" selected>', $state, '</option>';
      }
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo chr(10), '</select>', chr(10);
  }

  $zip = $f[zip];
  if ($hasZip) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="zip" maxlength="13" value="', $zip, '" placeHolder="CEP" style="width: 6em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $twitter = $f[twitter];
  if ($hasTwitter) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="twitter" maxlength="51" value="', $twitter, '" placeHolder="Twitter" style="width: 8em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $phone = $f[phone];
  if ($hasPhone) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="phone" maxlength="21" value="', $phone, '" placeHolder="Telefone" style="width: 7em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $url = $f[url];
  if ($hasUrl) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="url" maxlength="256" value="', $url, '" placeHolder="Website" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $description = htmlentities($f[description]);
  if ($hasDesc) {
    echo '<input type="text" dojoType="dijit.form.TextBox" name="description" maxlength="300" value="', $description, '" placeHolder="Descri&ccedil;&atilde;o" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
  }

  $ll = $f[ll];
  if ($hasLl) {
    if (($ll != '') && ($ll != ' ')) {
      echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" maxlength="402" value="', $ll, '" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" onFocus="window.temp=this.value" onBlur="if (window.temp != this.value) dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'" onChange="dojo.byId(\'result', $i - 1, '\').innerHTML=\'\'">', chr(10);
    } else {
      echo '<input type="text" dojoType="dijit.form.TextBox" name="ll" placeHolder="Lat/Long" style="width: 9em; margin-left: 5px;" disabled>', chr(10);
    }
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
