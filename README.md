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
    "tag" => "samplecontent"
);

print_r ( $doc->query($URN)->submit($options) );
```

### See more

[Getting started documentation](https://github.com/publicmediaplatform/pmpdocs/wiki#getting-started)

## Tests

The SDK unit tests use the TAP protocol (http://testanything.org/) and
require the *prove* command, part of the standard Perl distribution
on most Linux and UNIX systems.

The test suite can be invoked with:

    % make test


In order to run the full suite with server interaction, the
*PMP_CLIENT_ID* and *PMP_CLIENT_SECRET* environment variables must be set.
Example:

    % PMP_CLIENT_ID=foobar PMP_CLIENT_SECRET=mysecret make test

To debug the tests, set the *REST_AGENT_DEBUG* environment variable to
a true value (*REST_AGENT_DEBUG=1*).
