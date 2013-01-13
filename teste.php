<?php 
	require_once("FoursquareAPI.Class.php");
	$ll = array_key_exists("ll",$_GET) ? $_GET['ll'] : "Lat/Long";
?>
<!doctype html>
<html>
<head>
	<title>PHP-Foursquare :: Authenticated Request Example</title>
	<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
	<meta http-equiv="cache-control" content="no-cache"/>
	<meta http-equiv="pragma" content="no-cache">
</head>
<body>
<h1>Authenticated Request Example</h1>
<p>
	Search for venues by lat/long...
	<form action="" method="GET">
		<input type="text" name="ll" />
		<input type="submit" value="Search!" />
	</form>
<p>Searching for venues near <?php echo $ll; ?></p>
<hr />
<?php 
	// Set your client key and secret
	$client_key = "3EZPQCWMPTP0TLV4SJNPOLMWJB4UVCBGMADXWQCYFU3MPIQZ";
	$client_secret = "J2310KS05Z50PU44DUC0T0HPEYM2CEQKBBPROAGXMBACZRZG";  
	// Set your auth token, loaded using the workflow described in tokenrequest.php
	$auth_token = "PUY0Y4FMSGSXNR24BMSEK50FHVLPKGSRLUPBJSSY1UAEP2U3";
	// Load the Foursquare API library
	$foursquare = new FoursquareAPI($client_key,$client_secret);
	$foursquare->SetAccessToken($auth_token);

	// Prepare parameters
	$params = array("ll"=>$ll, "limit"=>50);

	// Perform a request to a authenticated-only resource
	$response = $foursquare->GetPrivate("venues/search",$params);
	$venues = json_decode($response);
	//print_r($venues);
	//exit;

	// NOTE:
	// Foursquare only allows for 500 api requests/hr for a given client (meaning the below code would be
	// a very inefficient use of your api calls on a production application). It would be a better idea in
	// this scenario to have a caching layer for user details and only request the details of users that
	// you have not yet seen. Alternatively, several client keys could be tried in a round-robin pattern 
	// to increase your allowed requests.

?>
	<ul>
		<?php foreach($venues->response->venues as $venue): ?>
			<li>
				<?php 
					if(property_exists($venue,"name")) echo $venue->name;
					if(property_exists($venue,"id")) echo " (" . $venue->id . ")";

					// Grab user twitter details
					//$request = $foursquare->GetPrivate("users/{$user->id}");
					//$details = json_decode($request);
					//$u = $details->response->user;
					//if(property_exists($u->contact,"twitter")){
						//echo " -- follow this user <a href=\"http://www.twitter.com/{$u->contact->twitter}\">@{$u->contact->twitter}</a>";
					//}

				?>
			
			</li>
		<?php endforeach; ?>
	</ul>
</body>
</html>