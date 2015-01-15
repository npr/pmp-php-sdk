#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// follow some links to related docs
//

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(8);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// query docs
$opts = array('limit' => 1, 'profile' => 'story', 'has' => 'image');
ok( $doc = $sdk->queryDocs($opts), 'query docs' );
is( count($doc->items), 1, 'query docs - count items' );

// load the creator link
$items = $doc->items();
$first_item = $items[0];
$creator_links = $first_item->links('creator');
is( count($creator_links), 1, 'links - has creator' );
ok( $creator = $creator_links[0]->follow(), 'links - follow creator' );
is( $creator->href, $creator_links[0]->href, 'links - creator href' );
ok( $creator->attributes->guid, 'links - creator guid' );
ok( $creator->attributes->title, 'links - creator title' );
