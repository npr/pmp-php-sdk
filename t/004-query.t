#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';
$PMP_USER = 'af676335-21df-4486-ab43-e88c1b48f026';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(45);
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
is( count($items), 4, 'query items - array length' );
is( $items->pageNum(), 1, 'query items - page number' );
cmp_ok( $items->totalItems(), '>', 4, 'query items - total' );
cmp_ok( $items->totalPages(), '>', 1, 'query items - total pages' );

// spot check the items
$guids_seen = array();
foreach ($items as $idx => $item) {
    ok( $item, "query items - $idx not null" );
    ok( $item->attributes->guid, "query items - $idx guid" );
    ok( $item->attributes->title, "query items - $idx title" );
    $guids_seen[$item->attributes->guid] = true;
}

// iterate over a couple pages
ok( $iter = $doc->itemsIterator(3), 'query iterator' );
$pages = array();
foreach ($iter as $pageNum => $items) {
    $pages[$pageNum] = $items;
}

is( count($pages), 3, 'query iterator - count' );
foreach ($pages[1] as $idx => $item) {
    ok( isset($guids_seen[$item->attributes->guid]), "query page 1 - $idx already seen" );
    $guids_seen[$item->attributes->guid] = true;
}
foreach ($pages[2] as $idx => $item) {
    ok( !isset($guids_seen[$item->attributes->guid]), "query page 2 - $idx not seen" );
    $guids_seen[$item->attributes->guid] = true;
}
foreach ($pages[3] as $idx => $item) {
    ok( !isset($guids_seen[$item->attributes->guid]), "query page 3 - $idx not seen" );
    $guids_seen[$item->attributes->guid] = true;
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
is( $doc->items()->totalItems(), 2, 'query by shortcut - total' );
is( $doc->items()->totalPages(), 1, 'query by shortcut - total pages' );

// query 404 via shortcut
try {
    $opts = array('profile' => 'foobar');
    $doc = CollectionDocJson::search($host, $auth, $opts);
    is( $doc, null, '404 by shortcut - returns null instead of throwing up');
}
catch (Exception $ex) {
    fail('404 by shortcut - returns null instead of throwing up');
}
