<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

$VERSION = "List Venues Editor 0.1 beta";

$oauth_token = $_POST["oauth_token"];

if (is_uploaded_file($_FILES['txt']['tmp_name'])) {
  $txt = $_FILES['txt']['tmp_name'];
  $file = file($txt);
  //print_r($file);
  if (count($file) > 500) {
    echo '<html><head><title>', $VERSION, '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><style type="text/css">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style></head><body><p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas do arquivo e tente novamente.<p><button type="button" onclick="history.go(-1)">Voltar</button></p></body></html>';
    exit;
  }
  if (stripos($file[0], "foursquare.com/v/") === false) {
    echo '<html><head><title>', $VERSION, '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><style type="text/css">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style></head><body><p>Erro na leitura do endere&ccedil;o das venues!</p><p>Verifique o arquivo e tente novamente.<p><button type="button" onclick="history.go(-1)">Voltar</button></p></body></html>';
    exit;
  }
  $venues = array();
  $i = 0;
  //echo '<br><br>';
  //print_r($file);
  foreach ($file as &$f) {
    $l = strlen($f) - 2;
    if ($f[$l] === "/")
      $f = substr($f, 0, $l);
    $f = str_replace("/edit", "", $f);
    $venues[$i] = substr($f, strrpos($f, "/") + 1, 24);
    $i++;
  }
  unset($f); // break the reference with the last element
  //echo '<br><br>';
  //print_r($file);
  //echo '<br><br>';
  //print_r($venues);
  //exit;
} else {
  echo '<html><head><title>', $VERSION, '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><style type="text/css">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style></head><body>Erro ao enviar o arquivo!</body></html>';
  exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
<title><?php echo $VERSION;?></title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
<style type="text/css">
  body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }
</style>
<script type="text/javascript">
function xmlhttpRequest(metodo, endpoint, dados, i) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        if (metodo == "POST")
          //document.getElementById("result" + i).innerHTML = xmlhttp.responseText;
          document.getElementById("result" + i).innerHTML = "OK";
        else
          atualizarTabela(resposta, i);
      else if (xmlhttp.status == 400)
        document.getElementById("result" + i).innerHTML = "Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 401)
        document.getElementById("result" + i).innerHTML = "Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 403)
        document.getElementById("result" + i).innerHTML = "Erro 403: Forbidden";
      else if (xmlhttp.status == 404)
        document.getElementById("result" + i).innerHTML = "Erro 404: Not Found";
      else if (xmlhttp.status == 405)
        document.getElementById("result" + i).innerHTML = "Erro 405: Method Not Allowed";
      else if (xmlhttp.status == 500)
        document.getElementById("result" + i).innerHTML = "Erro 500: Internal Server Error";
      else
        document.getElementById("result" + i).innerHTML = "Erro desconhecido: " + xmlhttp.status;
    }
  }
  xmlhttp.open(metodo, endpoint, true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(dados);
  return false;
}
function atualizarTabela(resposta, i) {
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
    if (document.forms[i].elements[j].value == "undefined")
      document.forms[i].elements[j].value = "";
    //document.getElementById("result" + i).innerHTML = "Ok!";
  }
}
function carregarVenues() {
  var oauth_token = "<?php echo $oauth_token;?>";
  var venue, resposta;
  document.getElementById("result").innerHTML = "Recuperando dados...";
  for (i = 0; i < document.forms.length; i++) {
    venue = document.forms[i]["venue"].value;
    xmlhttpRequest("GET", "https://api.foursquare.com/v2/venues/" + venue + "?oauth_token=" + oauth_token + "&v=20111121", null, i);
  }
  document.getElementById("result").innerHTML = "Dados recuperados!";
}
function salvarVenues() {
  var oauth_token = "<?php echo $oauth_token;?>";
  var venue, dados, ll;
  document.getElementById("result").innerHTML = "Enviando dados...";
  for (i = 0; i < document.forms.length; i++) {
    dados = "oauth_token=" + oauth_token;
    for (j = 1; j < document.forms[i].elements.length; j++) {
      venue = document.forms[i]["venue"].value;
      if (document.forms[i].elements[j].name != "ll")
        dados += "&" + document.forms[i].elements[j].name + "=" + document.forms[i].elements[j].value;
      else {
        ll = document.forms[i]["ll"].value;
        if (ll != null && ll != "")
          dados += "&ll=" + document.forms[i]["ll"].value;
      }
    }
    dados += "&v=20111121";
    //document.getElementById("result").innerHTML += "<br>venue=" +venue + "<br>dados=" + dados + "<br>result=result" + i;
    xmlhttpRequest("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", dados, i);
  }
  document.getElementById("result").innerHTML = "Dados enviados!";
}
</script>
</head>
<body onload="carregarVenues()">
<table>
<tr>
<th></th><?php
if (isset($_POST['nome'])) {
  $editName = true;
  echo '<th>Nome</th>';
} else {
  $editName = false;
}
if (isset($_POST['endereco'])) {
  $editAddress = true;
  echo '<th>Endere&ccedil;o</th>';
} else {
  $editAddress = false;
}
if (isset($_POST['ruacross'])) {
  $editCross = true;
  echo '<th>Rua Cross</th>';
} else {
  $editCross = false;
}
if (isset($_POST['cidade'])) {
  $editCity = true;
  echo '<th>Cidade</th>';
} else {
  $editCity = false;
}
if (isset($_POST['estado'])) {
  $editState = true;
  echo '<th>Estado</th>';
} else {
  $editState = false;
}
if (isset($_POST['cep'])) {
  $editZip = true;
  echo '<th>CEP</th>';
} else {
  $editZip = false;
}
if (isset($_POST['twitter'])) {
  $editTwitter = true;
  echo '<th>Twitter</th>';
} else {
  $editTwitter = false;
}
if (isset($_POST['telefone'])) {
  $editPhone = true;
  echo '<th>Telefone</th>';
} else {
  $editPhone = false;
}
if (isset($_POST['website'])) {
  $editUrl = true;
  echo '<th>Website</th>';
} else {
  $editUrl = false;
}
if (isset($_POST['descricao'])) {
  $editDesc = true;
  echo '<th>Descri&ccedil;&atilde;o</th>';
} else {
  $editDesc = false;
}
if (isset($_POST['latlong'])) {
  $editLl = true;
  echo '<th>Lat/Long</th>';
} else {
  $editLl = false;
}
echo '<th></th></tr>', chr(10);

$ufs = array("AC", "AL", "AP", "AM", "BA", "CE", "DF", "ES", "GO", "MA", "MT", "MS", "MG", "PA", "PB", "PR", "PE", "PI", "RJ", "RN", "RS", "RO", "RR", "SC", "SP", "SE", "TO");

$i = 0;

foreach ($file as $f) {
  $i++;

  echo '<tr>', chr(10), '<form name="form', $i, '" accept-charset="utf-8" encType="multipart/form-dados" method="post">', chr(10);

  $venue = $venues[$i - 1];
  echo '<td><input type="hidden" name="venue" value="', $venue, '"><a href="', $f, '" target="_blank">', $i, '</a></td>', chr(10);

  if ($editName) {
    echo '<td><input type="text" name="name" size="15" maxlenght="256"></td>', chr(10);
  }

  if ($editAddress) {
    echo '<td><input type="text" name="address" size="15" maxlength="128"></td>', chr(10);
  }

  if ($editCross) {
    echo '<td><input type="text" name="crossStreet" size="15" maxlength="51"></td>', chr(10);
  }

  if ($editCity) {
    echo '<td><input type="text" name="city" size="10" maxlength="31"></td>', chr(10);
  }

  if ($editState) {
    echo '<td><select name="state"><option value=""></option>';
    for ($j = 0; $j <= 26; $j++) {
      echo '<option value="', $ufs[$j], '">', $ufs[$j], '</option>';
    }
    echo '</select></td>', chr(10);
  }

  if ($editZip) {
    echo '<td><input type="text" name="zip" size="10" maxlength="13"></td>', chr(10);
  }

  if ($editTwitter) {
    echo '<td><input type="text" name="twitter" size="10" maxlength="51"></td>', chr(10);
  }

  if ($editPhone) {
    echo '<td><input type="text" name="phone" size="10" maxlength="21"></td>', chr(10);
  }

  if ($editUrl) {
    echo '<td><input type="text" name="url" size="15" maxlength="256"></td>', chr(10);
  }

  if ($editDesc) {
    echo '<td><input type="text" name="description" size="15" maxlength="300"></td>', chr(10);
  }

  if ($editLl) {
    echo '<td><input type="text" name="ll" size="15" maxlength="402"></td>', chr(10);
  }
  echo '<td><div id="result', $i - 1, '"></div></td>', chr(10), '</form>', chr(10), '</tr>', chr(10);
}
?>
</table>
<p><button type="button" onclick="salvarVenues()" name="submitButton">Salvar</button>
<button type="button" onclick="history.go(-1)" name="backButton">Voltar</button></p>
<br><p><b>Resultado</b><br>
<div id="result"></div><br>
</p>
</body>
</html>
