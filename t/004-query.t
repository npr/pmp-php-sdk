#!/usr/bin/env php
<?php
require_once 'Common.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';
require_once 'lib/Pmp/Sdk/CollectionDocJson.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';
$PMP_USER = 'af676335-21df-4486-ab43-e88c1b48f026';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(51);
ok( $auth = new AuthClient($host, $client_id, $client_secret), 'instantiate new AuthClient' );

// fetch the home doc
ok( $home = new CollectionDocJson($host, $auth), 'fetch home doc' );

// query docs
$opts = array('limit' => 4, 'profile' => 'user');
ok( $doc = $home->query('urn:collectiondoc:query:docs')->submit($opts), 'query docs' );
is( count($doc->items), 4, 'query docs - count items' );
is( count($doc->links->item), 4, 'query docs - count item links' );

// transform into items
ok( $items = $doc->items(), 'query items' );
is( $items->count(), 4, 'query items - count' );
is( count($items->toArray()), 4, 'query items - array length' );
is( $items->pageNum(), 1, 'query items - page number' );
cmp_ok( $items->total(), '>', 4, 'query items - total' );
cmp_ok( $items->numPages(), '>', 1, 'query items - total pages' );

// spot check the items
$page_one = array();
foreach ($items->toArray() as $idx => $item) {
    ok( $item, "query items - $idx not null" );
    ok( $item->attributes->guid, "query items - $idx guid" );
    ok( $item->attributes->title, "query items - $idx title" );
    $page_one[$item->attributes->guid] = true;
}

// TODO: more extensive iteration tests
ok( $iter = $items->getIterator(), 'query iterator' );
is( $iter->currentPageNum(), 1, 'query iterator - page number' );
ok( $iter->hasNext(), 'query iterator - has next' );
ok( !$iter->hasPrevious(), 'query iterator - has previous' );

// make sure the 2nd page looks different
ok( $next_page = $iter->next(), 'query next' );
is( $next_page->count(), 4, 'query next - count' );
is( count($next_page->toArray()), 4, 'query next - array length' );
is( $next_page->pageNum(), 2, 'query next - page number' );
foreach ($next_page->toArray() as $idx => $item) {
    ok( $item, "query next - $idx not null" );
    ok( $item->attributes->guid, "query next - $idx guid" );
    ok( !$page_one[$item->attributes->guid], "query next - $idx not in page 1" );
}

// query 404
$opts = array('limit' => 4, 'text' => 'thisprofiledoesnotexist');
try {
    $doc = $home->query('urn:collectiondoc:query:profiles')->submit($opts);
    fail( 'query 404 - exception thrown' );
}
catch (Exception $ex) {
    is( $ex->getCode(), 404, 'query 404 - exception thrown' );
}

// query via shortcut
$opts = array('guid' => $ARTS_TOPIC . ';' . $PMP_USER);
ok( $doc = CollectionDocJson::search($host, $auth, $opts), 'query by shortcut' );
is( count($doc->items), 2, 'query by shortcut - count items' );
is( count($doc->links->item), 2, 'query by shortcut - count item links' );
is( $doc->items()->pageNum(), 1, 'query by shortcut - page number' );
is( $doc->items()->total(), 2, 'query by shortcut - total' );
is( $doc->items()->numPages(), 1, 'query by shortcut - total pages' );

// query 404 via shortcut
try {
    $opts = array('profile' => 'foobar');
    $doc = CollectionDocJson::search($host, $auth, $opts);
    is( $doc, null, '404 by shortcut - returns null instead of throwing up');
}
catch (Exception $ex) {
    fail('404 by shortcut - returns null instead of throwing up');
}
