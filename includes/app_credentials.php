<?php
	// Set client key and secret
	// Prioritize environment variables (Docker/Server), fallback to hardcoded values for legacy setups
	$client_key = getenv('FOURSQUARE_CLIENT_KEY') ?: "YOUR_FOURSQUARE_CLIENT_KEY";
	$client_secret = getenv('FOURSQUARE_CLIENT_SECRET') ?: "YOUR_FOURSQUARE_CLIENT_SECRET";
	$redirect_uri = getenv('FOURSQUARE_REDIRECT_URI') ?: "http://localhost/4sqmet/index.php";
?>