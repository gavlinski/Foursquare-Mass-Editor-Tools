<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

$VERSION = "CSV Venues Editor 0.4 beta";

$oauth_token = $_POST["oauth_token"];

if (is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $csv = $_FILES['csv']['tmp_name'];
  require "CsvToArray.Class.php";
  $file = CsvToArray::open($csv);
  if (count($file) > 500) {
    echo '<html><head><title>', $VERSION, '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><style type="text/css">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style></head><body><p>O limite da API &eacute; de 500 requisi&ccedil;&otilde;es por hora por conjunto de endpoints por OAuth.</p><p>Reduza a quantidade de linhas do arquivo e tente novamente.<p><button type="button" onclick="history.go(-1)">Voltar</button></p></body></html>';
    exit;
  }
  if (array_key_exists("venue", $file[0]) == false) {
    echo '<html><head><title>', $VERSION, '</title><meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" /><style type="text/css">body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }</style></head><body><p>A coluna "venue" &eacute; obrigat&oacute;ria para a edi&ccedil;&atilde;o!</p><p>Verifique o arquivo CSV e tente novamente.<p><button type="button" onclick="history.go(-1)">Voltar</button></p></body></html>';
    exit;
  }
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
function xmlhttpPost(venue, dados, result) {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        document.getElementById(result).innerHTML = xmlhttp.responseText;
      else if (xmlhttp.status == 400)
        document.getElementById(result).innerHTML ="Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 401)
        document.getElementById(result).innerHTML ="Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 403)
        document.getElementById(result).innerHTML ="Erro 403: Forbidden";
      else if (xmlhttp.status == 404)
        document.getElementById(result).innerHTML ="Erro 404: Not Found";
      else if (xmlhttp.status == 405)
        document.getElementById(result).innerHTML ="Erro 405: Method Not Allowed";
      else if (xmlhttp.status == 500)
        document.getElementById(result).innerHTML ="Erro 500: Internal Server Error";
      else
        document.getElementById(result).innerHTML = "Erro desconhecido: " + xmlhttp.status;
    }
  }
  xmlhttp.open("POST", "https://api.foursquare.com/v2/venues/" + venue + "/edit", true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(dados);
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
    dados += "&v=20111119";
    //document.getElementById("result").innerHTML += "<br>venue=" +venue + "<br>dados=" + dados + "<br>result=result" + i;
    xmlhttpPost(venue, dados, "result" + i);
  }
  document.getElementById("result").innerHTML = "Dados enviados!";
}
</script>
</head>
<body>
<table>
<tr>
<th></th><?php
if (array_key_exists("name", $file[0])) {
  $hasName = true;
  echo '<th>Nome</th>';
} else {
  $hasName = false;
}
if (array_key_exists("address", $file[0])) {
  $hasAddress = true;
  echo '<th>Endere&ccedil;o</th>';
} else {
  $hasAddress = false;
}
if (array_key_exists("crossStreet", $file[0])) {
  $hasCross = true;
  echo '<th>Rua Cross</th>';
} else {
  $hasCross = false;
}
if (array_key_exists("city", $file[0])) {
  $hasCity = true;
  echo '<th>Cidade</th>';
} else {
  $hasCity = false;
}
if (array_key_exists("state", $file[0])) {
  $hasState = true;
  echo '<th>Estado</th>';
} else {
  $hasState = false;
}
if (array_key_exists("zip", $file[0])) {
  $hasZip = true;
  echo '<th>CEP</th>';
} else {
  $hasZip = false;
}
if (array_key_exists("twitter", $file[0])) {
  $hasTwitter = true;
  echo '<th>Twitter</th>';
} else {
  $hasTwitter = false;
}
if (array_key_exists("phone", $file[0])) {
  $hasPhone = true;
  echo '<th>Telefone</th>';
} else {
  $hasPhone = false;
}
if (array_key_exists("url", $file[0])) {
  $hasUrl = true;
  echo '<th>Website</th>';
} else {
  $hasUrl = false;
}
if (array_key_exists("description", $file[0])) {
  $hasDesc = true;
  echo '<th>Descri&ccedil;&atilde;o</th>';
} else {
  $hasDesc = false;
}
if (array_key_exists("ll", $file[0])) {
  $hasLl = true;
  echo '<th>Lat/Long</th>';
} else {
  $hasLl = false;
}
echo '</tr>', chr(10);

$i = 0;

foreach ($file as $f) {
  $i++;

  $formId = "form$i";
  echo '<tr>', chr(10), '<form name="', $formId, '" accept-charset="utf-8" encType="multipart/form-dados" method="post">', chr(10);

  $venue = $f[venue];
  echo '<td><input type="hidden" name="venue" value="', $venue, '"><a href="https://foursquare.com/v/', $venue, '" target="_blank">', $i, '</a></td>', chr(10);

  $name = htmlentities($f[name]);
  if ($hasName) {
    echo '<td><input type="text" name="name" size="15" maxlenght="256" value="', $name, '"></td>', chr(10);
  }

  $address = htmlentities($f[address]);
  if ($hasAddress) {
    echo '<td><input type="text" name="address" size="15" maxlength="128" value="', $address, '"></td>', chr(10);
  }

  $crossStreet = htmlentities($f[crossStreet]);
  if ($hasCross) {
    echo '<td><input type="text" name="crossStreet" size="15" maxlength="51" value="', $crossStreet, '"></td>', chr(10);
  }

  $city = htmlentities($f[city]);
  if ($hasCity) {
    echo '<td><input type="text" name="city" size="10" maxlength="31" value="', $city, '"></td>', chr(10);
  }

  $state = $f[state];
  if ($hasState) {
    echo '<td><select name="state"><option value="', $state, '">', $state, '</option></select></td>', chr(10);
  }

  $zip = $f[zip];
  if ($hasZip) {
    echo '<td><input type="text" name="zip" size="10" maxlength="13" value="', $zip, '"></td>', chr(10);
  }

  $twitter = $f[twitter];
  if ($hasTwitter) {
    echo '<td><input type="text" name="twitter" size="10" maxlength="51" value="', $twitter, '"></td>', chr(10);
  }

  $phone = $f[phone];
  if ($hasPhone) {
    echo '<td><input type="text" name="phone" size="10" maxlength="21" value="', $phone, '"></td>', chr(10);
  }

  $url = $f[url];
  if ($hasUrl) {
    echo '<td><input type="text" name="url" size="15" maxlength="256" value="', $url, '"></td>', chr(10);
  }

  $description = htmlentities($f[description]);
  if ($hasDesc) {
    echo '<td><input type="text" name="description" size="15" maxlength="300" value="', $description, '"></td>', chr(10);
  }

  $ll = $f[ll];
  if ($hasLl) {
    if (($ll != '') && ($ll != ' ')) {
      echo '<td><input type="text" name="ll" size="15" maxlength="402" value="', $ll, '"></td>', chr(10);
    } else {
      echo '<td><input type="text" name="ll" size="15" disabled></td>', chr(10);
    }
  }
  echo '</form>', chr(10), '</tr>', chr(10);
}
?>
</table>
<p><button type="button" onclick="salvarVenues()" name="submitButton">Salvar</button>
<button type="button" onclick="history.go(-1)" name="backButton">Voltar</button></p>
<p><b>Resultado</b><br>
<div id="result"></div><br>
<?php
$id = 0;
foreach ($file as $f) {
  echo ('<div id="result' . $id . '"></div>' . "\n");
  $id++;
}
?></p>
</body>
</html>
