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
<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
<script type="text/javascript">
dojo.require("dijit.form.Button");
dojo.require("dijit.form.TextBox");
dojo.require("dijit.form.Select")

function loadVenue() {
  var xmlhttp = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200) {
        //document.getElementById("result").innerHTML = xmlhttp.responseText;
        document.getElementById("venueid").innerHTML = 'Venue: <a href="' + resposta.response.venue.canonicalUrl + '" target="_blanck">' + resposta.response.venue.canonicalUrl + '</a>';
        document.forms["f"]["name"].value = resposta.response.venue.name;
        document.forms["f"]["address"].value = resposta.response.venue.location.address;
        document.forms["f"]["crossStreet"].value = resposta.response.venue.location.crossStreet;
        document.forms["f"]["city"].value = resposta.response.venue.location.city;
        document.forms["f"]["state"].value = resposta.response.venue.location.state;
        document.forms["f"]["zip"].value = resposta.response.venue.location.postalCode;
        document.forms["f"]["twitter"].value = resposta.response.venue.contact.twitter;
        document.forms["f"]["phone"].value = resposta.response.venue.contact.phone;
        document.forms["f"]["url"].value = resposta.response.venue.url;
        document.forms["f"]["description"].value = resposta.response.venue.description;
        document.forms["f"]["ll"].value = resposta.response.venue.location.lat + ', ' + resposta.response.venue.location.lng;
      }
      else if (xmlhttp.status == 400)
        document.getElementById("result").innerHTML = "Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 401)
        document.getElementById("result").innerHTML = "Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else
        document.getElementById("result").innerHTML = "Erro desconhecido: " + xmlhttp.status;
    }
  }
  xmlhttp.open("GET","https://api.foursquare.com/v2/venues/<?php echo $venue;?>?oauth_token=<?php echo $oauth_token;?>&v=20111120",true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(null);
}

function Salvar() {
  var xmlhttp = new XMLHttpRequest();
  document.getElementById("result").innerHTML = "Enviando dados...";
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4) {
      var resposta = JSON.parse(xmlhttp.responseText);
      if (xmlhttp.status == 200)
        document.getElementById("result").innerHTML = xmlhttp.responseText;
      else if (xmlhttp.status == 400)
        document.getElementById("result").innerHTML = "Erro 400: Bad Request, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else if (xmlhttp.status == 401)
        document.getElementById("result").innerHTML = "Erro 401: Unauthorized, Tipo: " + resposta.meta.errorType + ", Detalhe: " + resposta.meta.errorDetail;
      else
        document.getElementById("result").innerHTML = "Erro desconhecido: " + xmlhttp.status;
    }
  }
  var data = "oauth_token=" + "<?php echo $oauth_token;?>"
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
  data += "&v=20111120";
  document.getElementById("result").innerHTML = data;
  xmlhttp.open("POST","https://api.foursquare.com/v2/venues/<?php echo $venue;?>/edit",true);
  xmlhttp.setRequestHeader("Content-type","application/x-www-form-urlencoded");
  xmlhttp.send(data);
}
</script>
<link rel="stylesheet" type="text/css" href="http://ajax.googleapis.com/ajax/libs/dojo/1.6/dijit/themes/claro/claro.css"/>
</head> 
<body class="claro" onload="loadVenue()">
<h1>Venue Edit</h1>
<p id="venueid">Venue: <a href="https://foursquare.com/v/<?php echo $venue;?>" target="_blanck"><?php echo $venue;?></a></p>
<form name="f" accept-charset="utf-8" encType="multipart/form-data" action="" method="post">
<table style="border: 1px solid #9f9f9f;" cellspacing="10">
<tr>
<td><label for="name">Nome:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="name"></td>
</tr>
<tr>
<td><label for="name">Endere&ccedil;o:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="address"></td>
</tr>
<tr>
<td><label for="crossstreet">Rua Cross:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="crossStreet"></td>
</tr>
<tr>
<td><label for="city">Cidade:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="city"></td>
</tr>
<tr>
<td><label for="state">Estado:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="state"></td>
</tr>
<tr>
<td>
<label for="zip">CEP:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="zip"></td>
</tr>
<tr>
<td><label for="twitter">Twitter:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="twitter"></td>
</tr>
<tr>
<td><label for="phone">Telefone:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="phone"></td>
</tr>
<tr>
<td><label for="url">Website:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="url"></td>
</tr>
<tr>
<td><label for="description">Description:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="description"></td>
</tr>
<tr>
<td><label for="ll">Lat/Long:</td>
<td><input type="text" dojoType="dijit.form.TextBox" name="ll"></td>
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
