#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(16);
ok( $auth = new AuthClient($host, $client_id, $client_secret), 'instantiate new AuthClient' );

// fetch the home doc
ok( $home = new CollectionDocJson($host, $auth), 'fetch home doc' );

// fetch by guid
ok( $doc = $home->query('urn:collectiondoc:hreftpl:docs')->submit(array('guid' => $ARTS_TOPIC)), 'fetch by guid' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by guid - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by guid - title' );
like( $doc->links->profile[0]->href, '/profiles\/topic$/', 'fetch by guid - profile link' );

// fetch by alias
ok( $doc = $home->query('urn:collectiondoc:hreftpl:topics')->submit(array('guid' => 'arts')), 'fetch by alias' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by alias - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by alias - title' );
like( $doc->links->profile[0]->href, '/profiles\/topic$/', 'fetch by alias - profile link' );

// fetch 404
try {
    $doc = $home->query('urn:collectiondoc:hreftpl:topics')->submit(array('guid' => 'foobar'));
    fail( 'fetch 404 - exception thrown' );
}
catch (Exception $ex) {
    is( $ex->getCode(), 404, 'fetch 404 - exception thrown' );
}

// fetch via the query shortcut
ok( $doc = CollectionDocJson::search($host, $auth, array('guid' => $ARTS_TOPIC)), 'fetch by shortcut' );
is( $doc->attributes->guid, $ARTS_TOPIC, 'fetch by shortcut - guid' );
is( $doc->attributes->title, 'Arts', 'fetch by shortcut - title' );
like( $doc->getProfile()->href, '/profiles\/topic$/', 'fetch by shortcut - profile link' );

// fetch 404 via the query shortcut
try {
    $doc = CollectionDocJson::search($host, $auth, array('guid' => 'foobar'));
    is( $doc, null, '404 by shortcut - returns null instead of throwing up');
}
catch (Exception $ex) {
    fail('404 by shortcut - returns null instead of throwing up');
}
