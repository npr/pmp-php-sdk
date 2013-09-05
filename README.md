## PHP SDK for PMP API

This is a core SDK for Public Media Platform for PHP.

## Usage

### Authentication

require_once('path/to/lib/Pmp/AuthClient.php');
$client = new \Pmp\AuthClient('http://stage.pmp.io');

$client_id = '...';
$client_secret = '...';

$token = $client->getToken($client_id, $client_secret);
if ($token->expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday!");
}
$access_token = $token->access_token;

