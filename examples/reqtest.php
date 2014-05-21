<?php
/**
 * Test a simple document request using a URN for sample content from the PMP Sandbox API
 * 
 * Arguments:
 * -u Client Id
 * -p Client Secret
 */

require_once(dirname(__FILE__) . '/../lib/Pmp/Sdk/autoload.inc');

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as DocClient;

$options = getopt('u:p:');
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

$doc = new DocClient($apiUri, $authClient);

$URN = 'urn:collectiondoc:query:docs';

$options = array(
		"tag" => "samplecontent"
);

print_r ( json_encode($doc->query($URN)->submit($options), JSON_PRETTY_PRINT));