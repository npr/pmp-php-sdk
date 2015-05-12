#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// simple document fetch via the sdk
//

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(24);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// check the home doc
ok( $sdk->home, 'sdk home' );
like( $sdk->home->href, "#$host#", 'sdk home - href' );
is( $sdk->home->attributes->title, 'PMP Home Document', 'sdk home - title' );

// fetch by guid
ok( $doc = $sdk->fetchDoc($ARTS_TOPIC), 'fetch by guid' );
like( $doc->href, "/docs\/$ARTS_TOPIC/", 'fetch by guid - href' );
is( $doc->href, $sdk->hrefDoc($ARTS_TOPIC), 'fetch by guid - sdk href' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by guid - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by guid - title' );
like( $doc->links->profile[0]->href, '/profiles\/topic$/', 'fetch by guid - profile link' );
like( $doc->getProfile()->href, '/profiles\/topic$/', 'fetch by guid - profile shortcut' );
is( $doc->getProfileAlias(), 'topic', 'fetch by guid - profile alias' );

// fetch by alias
ok( $doc = $sdk->fetchTopic('arts'), 'fetch by alias' );
like( $doc->href, '/topics\/arts/', 'fetch by alias - href' );
is( $doc->href, $sdk->hrefTopic('arts'), 'fetch by guid - sdk href' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by alias - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by alias - title' );
like( $doc->getProfile()->href, '/profiles\/topic$/', 'fetch by alias - profile shortcut' );
is( $doc->getProfileAlias(), 'topic', 'fetch by alias - profile alias' );

// profile alias decoding
$doc->links->profile[0]->href = "$host/profiles/foobar";
is( $doc->getProfileAlias(), 'foobar', 'profile decoding - by alias' );
$doc->links->profile[0]->href = "$host/docs/c07bd70c-8644-4c5d-933a-40d5d7032036";
is( $doc->getProfileAlias(), 'series', 'profile decoding - by guid' );
$doc->links->profile[0]->href = "$host/profiles/c07bd70c-8644-4c5d-933a-40d5d7032036";
is( $doc->getProfileAlias(), 'series', 'profile decoding - by guid under profile endpoint' );

// fetch 404
is( $sdk->fetchDoc('foobar'), null, 'fetch guid 404 - returns null' );
is( $sdk->fetchTopic('foobar'), null, 'fetch alias 404 - returns null' );
