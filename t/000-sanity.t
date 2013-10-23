#!/usr/bin/env php
<?php
require_once 'Test.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';

if (getenv('PMP_CLIENT_ID') && getenv('PMP_CLIENT_SECRET')) {
    plan(2);
}
else {
    plan('skip_all', 'set PMP_CLIENT_ID and PMP_CLIENT_SECRET to run server tests');
}

$host = 'https://api-sandbox.pmp.io';
$client_id = getenv('PMP_CLIENT_ID');
$client_secret = getenv('PMP_CLIENT_SECRET');



ok( $auth = new \Pmp\Sdk\AuthClient($host, $client_id, $client_secret),
    "instantiate new AuthClient");

if ($auth->getToken()->token_expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday");
}

ok($token = $auth->getToken(), "getToken");
