#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// simple document fetch via the sdk
//

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(17);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// check the home doc
ok( $sdk->home, 'sdk home' );
like( $sdk->home->href, "#$host#", 'sdk home - href' );
is( $sdk->home->attributes->title, 'PMP Home Document', 'sdk home - title' );

// fetch by guid
ok( $doc = $sdk->fetchDoc($ARTS_TOPIC), 'fetch by guid' );
like( $doc->href, "/docs\/$ARTS_TOPIC/", 'fetch by guid - href' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by guid - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by guid - title' );
like( $doc->links->profile[0]->href, '/profiles\/topic$/', 'fetch by guid - profile link' );
like( $doc->getProfile()->href, '/profiles\/topic$/', 'fetch by guid - profile shortcut' );

// fetch by alias
ok( $doc = $sdk->fetchTopic('arts'), 'fetch by alias' );
like( $doc->href, '/topics\/arts/', 'fetch by alias - href' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by alias - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by alias - title' );
like( $doc->getProfile()->href, '/profiles\/topic$/', 'fetch by alias - profile shortcut' );

// fetch 404
is( $sdk->fetchDoc('foobar'), null, 'fetch guid 404 - returns null' );
is( $sdk->fetchTopic('foobar'), null, 'fetch alias 404 - returns null' );
