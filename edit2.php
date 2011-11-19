<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

$oauth_token = $_POST["oauth_token"];

if (is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $csv = $_FILES['csv']['tmp_name'];
  require "CsvToArray.Class.php";
  $file = CsvToArray::open($csv);
} else {
  echo ("<html><body>Erro no envio do arquivo!</body></html>");
  exit();
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
<title>CSV Venues Editor 0.4 beta</title>
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
    }
    ll = document.forms[i]["ll"].value;
    if (ll != null && ll != "")
      dados += "&ll=" + document.forms[i]["ll"].value;
    dados += "&v=20111119";
    //document.getElementById("result").innerHTML += "<br>venue=" +venue + "<br>dados=" + dados + "<br>result=result" + i;
    xmlhttpPost(venue, dados, "result" + i);
  }
  document.getElementById("result").innerHTML = "Dados enviados!";
}
</script>
</head>
<body>
<p><b>OAuth token:</b> <?php echo $oauth_token;?></p>
<table><tr><th>Nome</th><th>Endere&ccedil;o</th><th>Rua Cross</th><th>Cidade</th><th>Estado</th><th>CEP</th><th>Twitter</th><th>Telefone</th><th>Website</th><th>Descri&ccedil;&atilde;o</th><th>Lat/Long</th></tr>
<?php
$i = 0;

foreach ($file as $f) {
  $venue = $f[0];
  $name = htmlentities($f[1]);
  $address = htmlentities($f[2]);
  $crossStreet = htmlentities($f[3]);
  $city = htmlentities($f[4]);
  $state = $f[5];
  $zip = $f[6];
  $twitter = $f[7];
  $phone = $f[8];
  $url = $f[9];
  $description = htmlentities($f[10]);
  $ll = $f[11];

  $i++;

  $iId = "form$i";
?>
<tr>
<form name="<?php echo $iId;?>" accept-charset="utf-8" encType="multipart/form-dados" method="post">
<input type="hidden" name="venue" value="<?php echo $venue;?>">
<input type="hidden" name="name" value="<?php echo $name;?>">
<tr><td><a href="https://foursquare.com/v/<?php echo $venue;?>" target="_blank"><?php echo $name;?></a></td>
<td><input type="text" name="address" size="15" maxlength="128" value="<?php echo $address;?>"></td>
<td><input type="text" name="crossStreet" size="15" maxlength="51" value="<?php echo $crossStreet;?>"></td>
<td><input type="text" name="city" size="10" maxlength="31" value="<?php echo $city;?>"></td>
<td><select name="state"><option value="<?php echo $state;?>"><?php echo $state;?></option></select></td>
<td><input type="text" name="zip" size="10" maxlength="13" value="<?php echo $zip;?>"></td>
<td><input type="text" name="twitter" size="10" maxlength="51" value="<?php echo $twitter;?>"></td>
<td><input type="text" name="phone" size="10" maxlength="21" value="<?php echo $phone;?>"></td>
<td><input type="text" name="url" size="15" maxlength="256" value="<?php echo $url;?>"></td>
<td><input type="text" name="description" size="15" maxlength="300" value="<?php echo $description;?>"></td>
<?php
 if (($ll != '') && ($ll != ' ')) {
     echo ('<td><input type="text" name="ll" size="15" maxlength="402" value="' . $ll . '"></td>');
 } else {
     echo ('<td><input type="text" name="ll" size="15" disabled></td>');
 }
?>
</form>
</tr>
<?php
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
