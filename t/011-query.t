#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// search via the sdk
//

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';
$PMP_USER = 'af676335-21df-4486-ab43-e88c1b48f026';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(41);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// query docs
ok( $doc = $sdk->queryDocs(array('limit' => 4, 'profile' => 'user')), 'query docs' );
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
    is( $item->scope, 'read', "query items - $idx scope" );
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
$doc = $sdk->queryProfiles(array('limit' => 4, 'text' => 'thisprofiledoesnotexist'));
is( $doc, null, 'query 404 - returns null' );
