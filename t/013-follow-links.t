#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// follow some links to related docs
//

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(17);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// query docs
$opts = array('limit' => 1, 'profile' => 'story', 'has' => 'image,audio');
ok( $doc = $sdk->queryDocs($opts), 'query docs' );
is( count($doc->items), 1, 'query docs - count items' );

// load the creator link
$search_items = $doc->items();
$story = $search_items[0];
$creator_links = $story->links('creator');
is( count($creator_links), 1, 'links - has creator' );
ok( $creator = $creator_links[0]->follow(), 'links - follow creator' );
is( $creator->href, $creator_links[0]->href, 'links - creator href' );
ok( $creator->attributes->guid, 'links - creator guid' );
ok( $creator->attributes->title, 'links - creator title' );

// check items by profile
$items  = $story->items();
$images = $story->items('image');
$audios = $story->items('audio');
cmp_ok( $items->count(), '>=', 2, 'story items - at least 2 total' );
cmp_ok( $images->count(), '>=', 1, 'story items - at least 1 image' );
cmp_ok( $audios->count(), '>=', 1, 'story items - at least 1 audio' );
cmp_ok( $images->count(), '<', $items->count(), 'story items - less than total images' );
cmp_ok( $audios->count(), '<', $items->count(), 'story items - less than total audio' );
is( $images->count() + $audios->count(), $items->count(), 'story items - adds up' );
is( $story->items('foobar')->count(), 0, 'story items - no unknown profiles' );

// follow items by profile
is( $images[0]->getProfileAlias(), 'image', 'story image - profile alias' );
is( $audios[0]->getProfileAlias(), 'audio', 'story audio - profile alias' );
