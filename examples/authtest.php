<?php
/**
 * Test a simple authentication from the PMP Sandbox API
 *
 * Arguments:
 * -u Client Id
 * -p Client Secret
 */


require_once(dirname(__FILE__) . '/../lib/Pmp/Sdk/autoload.inc');
use \Pmp\Sdk\AuthClient as AuthClient;

$options = getopt('h:d:u:p:');
$clientId = $options['u'];
$clientSecret = $options['p'];


$apiUri = 'https://api-sandbox.pmp.io';

try {
	$authClient = new AuthClient($apiUri, $clientId, $clientSecret);
} catch (Exception $e) {
	echo "Failed authentication " . $apiUri . "\n";
	print_r($e->getDetails());
	exit;
}


if ($authClient->getToken()->token_expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday");
}

print_r($authClient->getToken());