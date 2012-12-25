<?php

/**
 * Foursquare Token Request
 *
 * Requisita um OAuth Token para autenticação do usuário
 *
 * @category	 Foursquare
 * @package		 Foursquare-Mass-Editor-Tools
 * @author		 Elio Gavlinski <gavlinski@gmail.com>
 * @copyright	 Copyleft (c) 2012
 * @version		 1.0
 * @link			 https://github.com/gavlinski/Foursquare-Mass-Editor-Tools/blob/master/index.php
 * @since			 File available since Release 1.5
 */

	require_once("FoursquareAPI.Class.php");

	/*** Set client key and secret ***/
	$client_key = "3EZPQCWMPTP0TLV4SJNPOLMWJB4UVCBGMADXWQCYFU3MPIQZ";
	$client_secret = "J2310KS05Z50PU44DUC0T0HPEYM2CEQKBBPROAGXMBACZRZG";
	$redirect_uri = "http://localhost/4sqmet/index.php";

	/*** Load the Foursquare API library ***/
	$foursquare = new FoursquareAPI($client_key, $client_secret);

	/*** If the link has been clicked, and we have a supplied code, use it to request a token ***/
	if (isset($_COOKIE['oauth_token'])) {
		$token = $_COOKIE['oauth_token'];
	} else if (array_key_exists("code", $_GET)) {
		$token = $foursquare->GetToken($_GET['code'], $redirect_uri);
	}
	
	/*** If we have not received a token, display the link for Foursquare webauth ***/
	if (!isset($token)) {

?>
<!doctype html>
<html>
<head>
	<title>Elio Tools</title>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8"/>
	<script src="js/dojo/dojo.js" djConfig="parseOnLoad: true"></script>
	<link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
	<link rel="stylesheet" type="text/css" href="js/dijit/themes/claro/claro.css"/>
	<link rel="stylesheet" type="text/css" href="estilo.css"/>
</head>
<body class="claro">
<p>
	<?php

		echo "<a href='" . $foursquare->AuthenticationLink($redirect_uri) . "'><img src='img/connect-blue@2x.png' alt='Connect to this app via Foursquare'></a>";

	?>
</p>
</body>
</html>
<?php

	// Otherwise save the token in a session variable and redirect browser
	} else {
		session_start();
		$_SESSION["oauth_token"] = $token;

		// Save and configure a cookie to expire in 15 days
		setcookie("oauth_token", $token, time()+60*60*24*15);
		header('Location: main.php');
	}
?>

