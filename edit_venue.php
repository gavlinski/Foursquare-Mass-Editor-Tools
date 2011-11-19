<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" );
ob_start("mb_output_handler");
header("Content-Type: text/html; charset=ISO-8859-1",true);

$venue = $_GET["venue"];
$oauth_token = $_GET["oauth"];
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html dir="ltr">
<head>
<title>Venue Editor</title>
<style type="text/css">
  body, html { font-family:helvetica,arial,sans-serif; font-size:90%; }
</style>
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true">
</script>
<script type="text/javascript">
dojo.require("dijit.form.Button");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.Select")

function Salvar() {
  var data = "oauth_token=" + document.forms["f"]["oauth_token"].value
    + "&name=" + document.forms["f"]["name"].value
    + "&address=" + document.forms["f"]["address"].value
    + "&crossStreet=" + document.forms["f"]["crossStreet"].value
    + "&city=" + document.forms["f"]["city"].value
    + "&state=" + document.forms["f"]["state"].value
    + "&zip=" + document.forms["f"]["zip"].value
    + "&twitter=" + document.forms["f"]["twitter"].value
    + "&phone=" + document.forms["f"]["phone"].value
    + "&url=" + document.forms["f"]["url"].value
    + "&description=" + document.forms["f"]["description"].value;
  var ll = document.forms["f"]["ll"].value;
  if (ll != null && ll != "") {
    data += "&ll=" + document.forms["f"]["ll"].value;
  }
  document.getElementById("result").innerHTML = data;
  var xmlhttp;
  xmlhttp = new XMLHttpRequest();
  document.getElementById("result").innerHTML = "Enviando dados...";
  xmlhttp.onreadystatechange=function() {
    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
      document.getElementById("result").innerHTML = xmlhttp.responseText;
    }
  }
  xmlhttp.open("POST","https://api.foursquare.com/v2/venues/<?php echo $venue;?>/edit",true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(data);
}
</script>
<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/dojo/1.6/dijit/themes/claro/claro.css"/>
</head> 
<body class="claro">
<h1>Venue Edit</h1>
<p>Venue: <a href="https://foursquare.com/v/<?php echo $venue;?>" target="_blanck"><?php echo $venue;?></a></p>
<form name="f" accept-charset="utf-8" encType="multipart/form-data" action="" method="post">
<input type="hidden" name="oauth_token" value="<?php echo $oauth_token;?>">
<table style="border: 1px solid #9f9f9f;" cellspacing="10">
<tr>
<td><label for="name">Nome:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="name" value="Voo Azul AD 4042"></td>
</tr>
<tr>
<td><label for="name">Endere&ccedil;o:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="address" value="Aeroporto Salgado Filho"></td>
</tr>
<tr>
<td><label for="crossstreet">Rua Cross:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="crossStreet" value="POA-VCP-CNF"></td>
</tr>
<tr>
<td><label for="city">Cidade:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="city" value="Porto Alegre"></td>
</tr>
<tr>
<td><label for="state">Estado:</td>
<td><select dojoType="dijit.form.Select" name="state">
<option value=""></option>
<option value="DF">DF</option>
<option value="RJ">RJ</option>
<option value="RS" selected="selected">RS</option>
<option value="SP">SP</option></select></td>
</tr>
<tr>
<td>
<label for="zip">CEP:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="zip" value="90200-000"></td>
</tr>
<tr>
<td><label for="twitter">Twitter:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="twitter" value="azulinhasaereas"></td>
</tr>
<tr>
<td><label for="phone">Telefone:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="phone" value="08008844040"></td>
</tr>
<tr>
<td><label for="url">Website:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="url" value="http://www.voeazul.com.br"></td>
</tr>
<tr>
<td><label for="description">Description:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="description" value="A mais nova e moderna companhia aérea brasileira. Passagens mais baratas, aviões modernos, sem poltrona do meio, programa de vantagens mais simples, snacks e bebidas, Tripulação treinada. Compre 30 dias antes e pague menos."></td>
</tr>
<tr>
<td><label for="ll">Lat/Long:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="ll" value="-29.990577, -51.181639"></td>
</tr>
</table>
<br>
<button type="button" dojoType="dijit.form.Button" onclick="Salvar()" name="submitButton">Salvar</button>
<button dojoType="dijit.form.Button" type="reset">Limpar</button>
</form>
<br><b>Resultado</b>
<div id="result"></div>
</body>
</html>
