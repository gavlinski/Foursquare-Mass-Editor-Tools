<?php
mb_internal_encoding("UTF-8");
mb_http_output( "iso-8859-1" ); 
ob_start("mb_output_handler");   
header("Content-Type: text/html; charset=ISO-8859-1",true);

$oauth_token = $_POST["oauth_token"];

if (is_uploaded_file($_FILES['csv']['tmp_name'])) {
  $csv = $_FILES['csv']['tmp_name'];
} else {
  echo ("<html><body>Erro no envio do arquivo!</body></html>");
  exit();
}

?>
<html>
<head>
<title>Csv Venues Edit</title>
<meta http-equiv="Content-type" content="text/html; charset=ISO-8859-1" />
</head>
<body>
<p><b>OAuth token:</b> <?php echo $oauth_token;?></p>
<table><tr><th>Nome</th><th>Endere&ccedil;o</th><th>Rua Cross</th><th>Cidade</th><th>Estado</th><th>CEP</th><th>Twitter</th><th>Telefone</th><th>Website</th><th>Descri&ccedil;&atilde;o</th><th>Lat/Long</th><th></th></tr>
<?php
require "CsvToArray.Class.php";
$file = CsvToArray::open($csv);

$form = 0;

foreach ($file as $f)
 {
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

	$form++;
?>
<form id="form<?php echo $form;?>" name="form<?php echo $form;?>" accept-charset="utf-8" method="post" action="https://api.foursquare.com/v2/venues/<?php echo $venue;?>/edit">
<input type="hidden" name="oauth_token" value="<?php echo $oauth_token;?>">
<input type="hidden" name="name" value="<?php echo $name;?>">
<tr><td><a href="https://foursquare.com/v/<?php echo $venue;?>" target="_blank"><?php echo $name;?></a></td>
<td><input type="text" name="address" size="15" maxlength="60" value="<?php echo $address;?>"></td>
<td><input type="text" name="crossStreet" size="15" maxlength="20" value="<?php echo $crossStreet;?>"></td>
<td><input type="text" name="city" size="10" maxlength="25" value="<?php echo $city;?>"></td>
<td><select name="state"><option value="<?php echo $stateAux;?>"><?php echo $state;?></option></select></td>
<td><input type="text" name="zip" size="10" maxlength="9" value="<?php echo $zip;?>"></td>
<td><input type="text" name="twitter" size="10" maxlength="30" value="<?php echo $twitter;?>"></td>
<td><input type="text" name="phone" size="10" maxlength="20" value="<?php echo $phone;?>"></td>
<td><input type="text" name="url" size="15" maxlength="50" value="<?php echo $url;?>"></td>
<td><input type="text" name="description" size="15" maxlength="300" value="<?php echo $description;?>"></td>
<?php
 if (($ll != '') && ($ll != ' ')) {
     echo ('<td><input type="text" name="ll" size="15" maxlength="50" value="' . $ll . '"></td>');
 } else {
     echo ('<td><input type="text" size="15" disabled></td>');
 }
?>
<td><input type="submit" name="submit"></td></tr>
</form>
<?php
 }
 ?>
</table>
</body>
</html>
