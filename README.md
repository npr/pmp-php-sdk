# PMP PHP SDK

[![Build Status](https://travis-ci.org/npr/pmp-php-sdk.svg?branch=master)](https://travis-ci.org/npr/pmp-php-sdk) [![Latest Stable Version](https://poser.pugx.org/publicmediaplatform/pmpsdk/v/stable)](https://packagist.org/packages/publicmediaplatform/pmpsdk)

A PHP API client for the [Public Media Platform](http://publicmediaplatform.org).

## Requirements

PHP version >= 5.5.  And a [PMP client-id/secret](https://support.pmp.io/login) for your PMP user.

## Installation

#### Via Composer

 1. Download [Composer](https://getcomposer.org/) (if you don't have it already), and install the `publicmediaplatform/pmpsdk` package:

 ```shell
 curl -sS https://getcomposer.org/installer | php
 php composer.phar require publicmediaplatform/pmpsdk
 ```

 2. Require the Composer-generated `autoload.php`

 ```php
 require 'vendor/autoload.php';
 ```

#### Via PHAR file

 1. Go to the [Latest Release](https://github.com/npr/pmp-php-sdk/releases/latest) of the `pmpsdk`
 2. Click the link to download `pmpsdk.phar`
 3. Require the file in your project:

 ```php
 require 'path/to/pmpsdk.phar`;
 ```

 **NOTE**: *if you see a strange error message full of question marks like `?r??PHP Fatal error:  Class 'Pmp\Sdk' not found`, make sure you turn off [detect unicode](http://stackoverflow.com/questions/11302593/how-to-disable-detect-unicode-setting-from-php-ini-trying-to-install-compose).*

## Usage

### Connecting

Simply instantiate a new SDK object using your credentials.  Errors will be immediately thrown if there's a problem fetching the API home doc or authenticating.

```php
try {
    $host = 'https://api-sandbox.pmp.io';
    $sdk = new \Pmp\Sdk($host, 'client-id', 'client-secret');
}
catch (\Pmp\Sdk\Exception\HostException $e) {
    echo "Invalid API host specified: $e";
    exit(1);
}
catch (\Pmp\Sdk\Exception\AuthException $e) {
    echo "Bad client credentials: $e";
    exit(1);
}
```

### Home Document

After successfully connecting, you can immediately interrogate the API home document - an instance of `\Pmp\Sdk\CollectionDocJson`.

```php
echo "HOME doc guid  = {$sdk->home->attributes->guid}\n";
echo "HOME doc title = {$sdk->home->attributes->title}\n";
```

### Fetching

To directly fetch a document (by guid or alias), the SDK provides shortcuts for locating links such as `urn:collectiondoc:hreftpl:docs` in the home document.  These shortcuts will always return `null` for HTTP 403 or 404 errors.

```php
$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';
$doc = $sdk->fetchDoc($ARTS_TOPIC);
if (!$doc) {
    echo "failed to fetch the ARTS topic - must have been a 403 or 404.\n";
    exit(1);
}
echo "ARTS topic href    = {$doc->href}\n";
echo "ARTS topic guid    = {$doc->attributes->guid}\n";
echo "ARTS topic title   = {$doc->attributes->title}\n";
echo "ARTS topic profile = {$doc->getProfile()}\n";
if ($doc->scope == 'write') {
    echo "And I can write to this, for some reason!\n";
}
else {
    echo "At least I can read it.\n"
}
```

Current `\Pmp\Sdk` fetch methods include:

* `$sdk->fetchDoc($guid)`
* `$sdk->fetchProfile($guid)`
* `$sdk->fetchSchema($guid)`
* `$sdk->fetchTopic($guid)`
* `$sdk->fetchUser($guid)`

### Querying

To query documents (by any [PMP search params](https://github.com/npr/pmp-docs-wiki/wiki/Querying-the-API)), the SDK provides shortcuts for locating links such as `urn:collectiondoc:query:docs`.  These shortcuts will always return `null` for HTTP 404 errors, indicating that your search yielded 0 total results.

```php
$doc = $sdk->queryDocs(array('limit' => 3, 'text' => 'penmanship'));
if (!$doc) {
    echo "got 0 results for my search - doh!\n";
    exit(1);
}

// use the "items" directly
$count1 = count($doc->items);
$title1 = $doc->items[0]->attributes->title;
echo "SEARCH - $count1 - $title1\n";

// or get a fancy items object with some helpers
$items = $doc->items();
$count2 = count($items);
$count3 = $items->count();
$title2 = $items[0]->attributes->title;
foreach ($items as $idx => $item) {
    echo "SEARCH item($idx) = {$item->attributes->title}\n";
    if ($item->scope == 'write') {
        $item->title = 'Wow, I can change the titles if I wanted to';
        $item->save();
    }
}
```

Current `\Pmp\Sdk` query methods include:

* `$sdk->queryCollection($collectionGuid, $params)`
* `$sdk->queryDocs($params)`
* `$sdk->queryGroups($params)`
* `$sdk->queryProfiles($params)`
* `$sdk->querySchemas($params)`
* `$sdk->queryTopics($params)`
* `$sdk->queryUsers($params)`

### Document Items

As seen above, you can use a Document's `items` (the expanded `links.item` array) directly as an array of `stdClass` objects.  And you can also expand them into a `\Pmp\Sdk\CollectionDocJsonItems` object for further shortcuts.

```php
$items = $doc->items();

// access the paging links via helpers
echo "SEARCH total   = {$doc->items()->totalItems()}\n";
echo "SEARCH items   = {$doc->items()->count()} items\n";
echo "SEARCH pagenum = {$doc->items()->pageNum()}\n";
echo "SEARCH pagenum = {$doc->items()->totalPages()}\n";
```

Sometimes you'll want to iterate over several pages of search results without directly following the `links.navigation` previous/next/first/last links.  You can easily get a `\Pmp\Sdk\PageIterator` for this purpose.  It accepts a `$pageLimit` paramater to limit the number of returned pages - or exclude the param to iterate over all pages.

```php
$pageLimit = 3;
foreach($doc->itemsIterator($pageLimit) as $pageNum => $items) {
    if ($pageNum < 1 || $pageNum > 3) {
        echo 'i did not see that one coming!';
        exit(1);
    }
    echo "SEARCH page $pageNum\n";
    foreach ($items as $idx => $item) {
        echo "  item($idx) = {$item->attributes->title}\n";
    }
}
```

Often, when fetching container docs such as "stories", you'll want to interrogate child items based on their profile types.  This SDK handles this for you, allowing the retrieval of just items-of-profile.

```php
echo "looking at a cdoc of profile = {$story->getProfileAlias()}\n";
$items = $story->items();
$audios = $story->items('audio');
$images = $story->items('image');
$videos = $story->items('video');

echo "  contains {$items->count()} items\n";
echo "           {$audios->count()} audios\n";
echo "           {$images->count()} images\n";
echo "           {$videos->count()} videos\n";
```

### Document Links

To navigate links, we can interrogate them directly on the `\Pmp\Sdk\CollectionDocJson` object, or browse them via the `\Pmp\Sdk\CollectionDocJsonLinks` object, containing a collection of `\Pmp\Sdk\CollectionDocJsonLink` objects.

Note that links have either an `href` or an `href-template` attribute.  To get a full URL from the link either way (and optionally passing an array of parameters), use the `expand()` method.

```php
$queryLinks = $doc->links('query');
if (empty($queryLinks)) {
    echo "document didn't have any links of reltype = query!\n";
    exit(1);
}
foreach ($queryLinks as $link) {
    $fakeParams = array('guid' => 'foobar');
    $url = $link->expand($fakeParams);
    echo "link = $url\n";
}
```

In some cases, we may know an `URN` (uniform resource name) of the link we're looking for.  In this case, we can directly fetch the link from the document.

```php
$link = $doc->link('urn:collectiondoc:query:profiles');
if (!$link) {
    echo "failed to find link in the document\n";
    exit(1);
}

// or only look in a specific link relType (links.query[])
$link = $doc->link('query', 'urn:collectiondoc:query:profiles');
```

To fetch the `\Pmp\Sdk\CollectionDocJson` at the other end of a link, simply `follow()` it.  This method optionally accepts the same array of `href-template` params as `expand()`.  If the url can't be loaded (404) or is inaccessible (403), `null` will be returned.

```php
$ownerLinks = $doc->links('owner');
if (empty($ownerLinks)) {
    echo "document didn't have an owner!\n";
    echo "which is really strange, because it's auto-generated by the API\n";
    exit(1);
}
$ownerLink = $ownerLinks[0];

// or use the shortcut
$ownerLink = $doc->getOwner();

// now follow the link
$ownerDoc = $ownerLink->follow();
if (!$ownerDoc) {
    echo "owner link must have been a 403 or 404!\n";
    exit(1);
}
echo "owner = {$ownerDoc->attributes->title}\n";
```

A document's `links.collection` will often contain PMP topics, series, properties, and contributors.  These links are normally distinguished by rels such as `urn:collectiondoc:collection:property`.  As a shortcut for finding these links, you can just refer to the last `property` segment of that urn.

```php
// these statements are equivalent
$links = $doc->links('collection');
$links = $doc->getCollections();

// these statements are also equivalent
$topicLinks = $doc->links('collection', 'urn:collectiondoc:collection:topic');
$topicLinks = $doc->getCollections('urn:collectiondoc:collection:topic');
$topicLinks = $doc->getCollections('topic');

// more examples...
$contribCount = $doc->getCollections('contributor')->count();
$firstSeriesLink = $doc->getCollections('series')->first();
$firstPropertyLink = $doc->getCollections('property')->first();
if ($firstPropertyLink) {
    $propertyDoc = $firstPropertyLink->follow();
    echo "Got a property - {$propertyDoc->attributes->title}\n";
}
```

### Modifying documents

To create a document, you should first know which [Profile Type](https://support.pmp.io/docs#profiles-and-schemas-hierarchy) you'd like to create.  Then use the SDK to instantiate a new `\Pmp\Sdk\CollectionDocJson` of that type.

```php
$data = array('attributes' => array('title' => 'foobar'));
$doc = $sdk->newDoc('story', $data);

// or alter the document data manually
$doc->attributes->title = 'foobar2';
$doc->attributes->valid = new \stdClass();
$doc->attributes->valid->to = '3013-07-04T04:00:44+00:00';

// save, but catch any pmp errors
try {
    $doc->save();
}
catch (\Pmp\Sdk\Exception\RemoteException $e) {
    echo "unable to create document: $e\n";
    exit(1);
}
```

To update documents, all you need is an instance of `\Pmp\Sdk\CollectionDocJson` that you can modify.  You can also catch any `\Pmp\Sdk\Exception\ValidationException` separately, to handle PMP schema violations separately.

```php
$doc->attributes->title = 'foobar3';
try {
    $doc->save();
}
catch (\Pmp\Sdk\Exception\ValidationException $e) {
    echo "invalid document: {$e->getValidationMessage()}\n";
    exit(1);
}
catch (\Pmp\Sdk\Exception\RemoteException $e) {
    echo "unable to save document: $e\n";
    exit(1);
}
```

To delete a document, just get an instance of `\Pmp\Sdk\CollectionDocJson` that you can modify.

```php
$doc->attributes->title = 'foobar3';
try {
    $doc->delete();
}
catch (\Pmp\Sdk\Exception\RemoteException $e) {
    echo "unable to delete document: $e\n";
    exit(1);
}
```

### Linking

One of the main concerns of the PMP are document links.  The syntax basically involves manipulating PHP `array` and `stdClass` to represent your links.  For instance, to set an alternate link on a story:

```php
$doc->links->alternate = array(new \stdClass());
$doc->links->alternate[0]->title = 'Foobar Home Page';
$doc->links->alternate[0]->href = 'http://foo.edu/bar';
$doc->save();
```

When linking to other docs within the PMP, you should first load those documents to make sure they exist.  The exception to this is when you're using a known alias for a `topic`, `profile`, etc.  Here's an example of several different kinds of links:

```php
// let's create-or-update an image, with a known guid
$img = $sdk->newDoc('image', array(
    'attributes' => array(
        'guid'        => $KNOWN_IMAGE_GUID,
        'title'       => 'Alternate text here',
        'byline'      => 'Myimage Credit',
        'description' => 'This is a caption for this image',
    ),
    'links' => (
        'enclosure' => array(
            array(
                'href' => 'http://path/to/image/thumbnail.jpg',
                'type' => 'image/jpeg',
                'meta' => array(
                    'crop'   => 'small',
                    'height' => '100',
                    'width'  => '50',
                ),
            ),
            array(
                'href' => 'http://path/to/image/fullsize.jpg',
                'type' => '',
                'meta' => array(
                    'crop'   => 'primary',
                    'height' => '1000',
                    'width'  => '500',
                ),
            ),
        ),
    ),
));
$img->save();

// now attach it to the story
$doc->links->item = array(new \stdClass());
$doc->links->item[0]->href = $img->href;

// put the story in a topic, while we're at it
$doc->links->collection = array(new \stdClass());
$doc->links->collection[0]->href = $sdk->hrefTopic('arts');
$doc->save();
```

## Caching

Document-level caching is not yet implemented (see #21).  But you can cache the `\Pmp\Sdk` itself, to optimize requests for the PMP home-doc and Oauth tokens across your application requests.

```php
$sdk = new \Pmp\Sdk($host, 'client-id', 'client-secret');
$cache_str = serialize($sdk);
$my_cache_mechanism->set('pmpsdk', $cache_str, 3600);

// awhile later, in a different HTTP request
$cache_str = $my_cache_mechanism->get('pmpsdk');
if ($cache_str) {
    try {
        $sdk = unserialize($cache_str);
        if ($sdk === false) {
            throw new \RuntimeException('Failed to unserialize SDK');
        }
    } catch (\RuntimeException $e) {
        // failed to unserialize SDK, so need to start fresh
        $sdk = new \Pmp\Sdk($host, 'client-id', 'client-secret'); 
    }
    // if retrieved from cache successfully, this would only generate 1 request, 
    // since we would already have both the PMP home doc and an auth token
    $doc = $sdk->fetchDoc('SOME-GUID');
}
```

Note that if the serialized string is somehow corrupt:
* for PHP 7.2.8 and later, the call to `unserialize()` will return `false` and also might generate a PHP `RuntimeException` 
* for PHP 7.2.7 and earlier, the call to `unserialize()` will generate a PHP `RuntimeException`


## Developing

To get started on development, check out the this repo, and run a `make install`.  (This requires Composer be present on your system).

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

To debug the HTTP calls occuring during the tests, set the *DEBUG* environment variable to 1 with `DEBUG=1 make test`.

To build a [PHAR](http://php.net/manual/en/intro.phar.php) version of this SDK, run `make build`.

## Issues and Contributing

Report any bugs or feature-requests via the issue tracker.

## License

The PMP PHP SDK is free software, and may be redistributed under the MIT-LICENSE.

Thanks for listening!
