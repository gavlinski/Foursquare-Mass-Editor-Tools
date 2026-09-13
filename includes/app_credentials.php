<?php
	// Set client key and secret
	// Prioritize environment variables (Docker/Server), fallback to hardcoded values for legacy setups
	$client_key = getenv('FOURSQUARE_CLIENT_KEY') ?: ($_ENV['FOURSQUARE_CLIENT_KEY'] ?? '');
	$client_secret = getenv('FOURSQUARE_CLIENT_SECRET') ?: ($_ENV['FOURSQUARE_CLIENT_SECRET'] ?? '');
	$redirect_uri = getenv('FOURSQUARE_REDIRECT_URI') ?: ($_ENV['FOURSQUARE_REDIRECT_URI'] ?? "https://localhost/4sqmet/index.php");
?>