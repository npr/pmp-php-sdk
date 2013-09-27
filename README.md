## PHP SDK for PMP API

This is a core SDK for Public Media Platform for PHP.

## Usage

### Authentication

```php
require_once('path/to/lib/Pmp/Sdk/AuthClient.php');
$client_id = '...';
$client_secret = '...';
$auth = new \Pmp\Sdk\AuthClient('http://stage.pmp.io', $client_id, $client_secret);

$token = $auth->getToken();
if ($token->expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday!");
}
$access_token = $token->access_token;
```

### Making a request

```php
require_once('path/to/lib/Pmp/Sdk/CollectionDocJson.php');
$doc = new \Pmp\Sdk\CollectionDocJson($host, $auth);

$URN = 'urn:pmp:query:docs';

$options = array(
    "language" => "en"
);

print_r ( $doc->query($URN)->submit($options) );
```

