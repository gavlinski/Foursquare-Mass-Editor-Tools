<?php
session_start();
$_SESSION['oauth_token'] = 'test_token_123';
$_SESSION['user_data'] = [
    'firstName' => 'Elio',
    'lastName' => 'Gavlinski',
    'id' => '12345',
    'checkins' => ['count' => 1234]
];
echo "Session created: " . session_id();
?>
