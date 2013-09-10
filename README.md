## PHP SDK for PMP API

This is a core SDK for Public Media Platform for PHP.

## Usage

### Authentication

```php
require_once('path/to/lib/Pmp/Sdk/AuthClient.php');
$client = new \Pmp\Sdk\AuthClient('http://stage.pmp.io');

$client_id = '...';
$client_secret = '...';

$token = $client->getToken($client_id, $client_secret);
if ($token->expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday!");
}
$access_token = $token->access_token;
```

### Making a request

```php
require_once('path/to/lib/Pmp/Sdk/CollectionDocJson.php');
$doc = new \Pmp\Sdk\CollectionDocJson($host, $access_token);

$URN = 'urn:pmp:search:docs';

$options = array(
    "language" => "en"
);

print_r ( $doc->search($URN)->submit($options) );
```

