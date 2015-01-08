#!/usr/bin/env php
<?php
require_once 'Common.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';
require_once 'lib/Pmp/Sdk/CollectionDocJson.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(9);
ok( $auth = new AuthClient($host, $client_id, $client_secret), 'instantiate new AuthClient' );

// fetch the home doc
ok( $home = new CollectionDocJson($host, $auth), 'fetch home doc' );

// query docs
$opts = array('limit' => 1, 'profile' => 'story', 'has' => 'image');
ok( $doc = $home->query('urn:collectiondoc:query:docs')->submit($opts), 'query docs' );
is( count($doc->items), 1, 'query docs - count items' );

// load the creator link
$first_item = $doc->items()[0];
is( count($first_item->links('creator')), 1, 'links - has creator' );
ok( $creator = $first_item->links('creator')[0]->follow(), 'links - follow creator' );
is( $creator->href, $first_item->links('creator')[0]->href, 'links - creator href' );
ok( $creator->attributes->guid, 'links - creator guid' );
ok( $creator->attributes->title, 'links - creator title' );
