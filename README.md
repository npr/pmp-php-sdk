# PMP PHP SDK

[![Build Status](https://travis-ci.org/publicmediaplatform/phpsdk.svg?branch=master)](https://travis-ci.org/publicmediaplatform/phpsdk)

A set of PHP classes, providing an API client for the [Public Media Platform](http://publicmediaplatform.org).

## Installation

Copy this repo into your project directory, and require the classes you need at
the top of your files.  TODO: composer/packagist.

The only requirement is a PHP version >= 5.3.

## Usage

You can find some more in-depth examples in the [PMP API Docs](http://support.pmp.io/docs).  But if you've generated an oauth client using the [Support app](http://support.pmp.io/login), you can use the SDK as follows...

```php
require_once('path/to/lib/Pmp/Sdk/AuthClient.php');
require_once('path/to/lib/Pmp/Sdk/CollectionDocJson.php');

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

// let's authenticate with the sandbox api
$host = 'https://api-sandbox.pmp.io';
$auth = new AuthClient($host, 'my_client_id', 'my_client_secret');

// now, try searching using the query:docs link
$home = new CollectionDocJson($host, $auth);
$opts = array('profile' => 'story', 'text' => 'Penmanship');
try {
    $search = $home->query('urn:collectiondoc:query:docs')->submit($opts);
}
catch (Exception $ex) {
    if ($ex->getCode() == 404) {
        echo "Woh - empty search results";
    }
    else {
        throw $ex; // re-throw
    }
}

// or, if you'd rather not catch 404's for empty search results
$search = CollectionDocJson::search($host, $auth, $opts);
if ($search == null) {
    echo "Sorry - no search results";
    return;
}

// let's look at the results
$items = $search->items();
echo "Looking at " . $items->count() . " items of " . $items->total();
echo "Page " . $items->pageNum() . " of " . $items->numPages();
foreach ($items->toArray() as $item) {
    echo "Guid = " . $item->attributes->guid;
    echo "Title = " . $item->attributes->title;
}

// now follow a link
$first_item = $items[0];
if (!empty($first_item->links('creator'))) {
    $creator = $first_item->links('creator')[0]->follow();
    echo "Got creator = " . $item->attributes->title;
}
```

## Developing

This module is tested using the [TAP protocol](http://testanything.org) and requires the *prove* command, part of the standard Perl distribution on most Linux and UNIX systems.  You'll also need to provide some valid PMP credentials.

The test suite can be invoked as follows...

```shell
$ export PMP_HOST=https://api-sandbox.pmp.io
$ export PMP_USERNAME=myusername
$ export PMP_PASSWORD=password1234
$ export PMP_CLIENT_ID=my_client_id
$ export PMP_CLIENT_SECRET=my_client_secret
$
$ make test
```

To debug the tests, set the *REST_AGENT_DEBUG* environment variable to a true value (*REST_AGENT_DEBUG=1*).

## Issues and Contributing

Report any bugs or feature-requests via the issue tracker or snapchat.

## License

The PMP `phpsdk` is free software, and may be redistributed under the MIT-LICENSE.

Thanks for listening!
