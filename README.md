## PHP SDK for PMP API

This is a core SDK for Public Media Platform for PHP.

## Usage

### Authentication

```php
require_once('path/to/lib/Pmp/Sdk/AuthClient.php');

// Sandbox
$host = 'https://api-sandbox.pmp.io';
$client_id = '...';
$client_secret = '...';

$auth = new \Pmp\Sdk\AuthClient($host, $client_id, $client_secret);

if ($auth->getToken()->token_expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday");
}

print_r($auth->getToken());
```

### Making a request

```php
require_once('path/to/lib/Pmp/Sdk/CollectionDocJson.php');
$doc = new \Pmp\Sdk\CollectionDocJson($host, $auth);

$URN = 'urn:pmp:query:docs';

$options = array(
    "tags" => "samplecontent"
);

print_r ( $doc->query($URN)->submit($options) );
```

