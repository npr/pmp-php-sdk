#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(16);
ok( $auth = new AuthClient($host, $client_id, $client_secret), 'instantiate new AuthClient' );

// fetch the home doc
ok( $home = new CollectionDocJson($host, $auth), 'fetch home doc' );

// test document
$TEST_GUID = '8def7ee1-6a9d-407d-b269-538bb454ad1e';
$TEST_DOC = array(
    'attributes' => array(
        'guid'  => $TEST_GUID,
        'title' => 'PMP PHP SDK Test Document',
        'tags'  => array('pmp_php_sdk_test_doc'),
    ),
    'links' => array(
        'profile' => array(
            array('href' => $host . '/profiles/story')
        ),
    ),
);

// 1) create
$doc = new CollectionDocJson($host, $auth);
try {
    $doc->setDocument($TEST_DOC);
    $doc->save();
    pass( 'create - ok' );
}
catch (Exception $ex) {
    fail( "unable to create document: $ex" );
}

// 2) read
try {
    ok( $fetched = $home->query('urn:collectiondoc:hreftpl:docs')->submit(array('guid' => $TEST_GUID)), 'read by guid' );
    pass( 'read by guid - ok' );

    // check data
    is( $fetched->attributes->guid, $TEST_GUID, 'read by guid - guid' );
    is( $fetched->attributes->title, 'PMP PHP SDK Test Document', 'read by guid - title' );
    like( $fetched->links->profile[0]->href, '/profiles\/story$/', 'read by guid - profile link' );
}
catch (Exception $ex) {
    fail( "unable to read document: $ex" );
    fail( 'read by guid - guid' );
    fail( 'read by guid - title' );
    fail( 'read by guid - profile link' );
}

// 3) update
try {
    $doc->attributes->title = 'zzz PMP PHP SDK Test Doc';
    $doc->save();
    pass( 'update - ok' );
}
catch (Exception $ex) {
    fail( "unable to update document: $ex" );
}

// 3.5) re-read after update
try {
    ok( $fetched = $home->query('urn:collectiondoc:hreftpl:docs')->submit(array('guid' => $TEST_GUID)), 'upread by guid' );
    pass( 'upread by guid - ok' );

    // check data
    is( $fetched->attributes->guid, $TEST_GUID, 'upread by guid - guid' );
    is( $fetched->attributes->title, 'zzz PMP PHP SDK Test Doc', 'upread by guid - title' );
    like( $fetched->links->profile[0]->href, '/profiles\/story$/', 'upread by guid - profile link' );
}
catch (Exception $ex) {
    fail( "unable to read document: $ex" );
    fail( 'upread by guid - guid' );
    fail( 'upread by guid - title' );
    fail( 'upread by guid - profile link' );
}

// 4) delete
try {
    $doc->delete();
    pass( 'delete - ok' );
}
catch (Exception $ex) {
    fail( "unable to delete document: $ex" );
}

// 4.5) re-read after delete
try {
    $home->query('urn:collectiondoc:hreftpl:docs')->submit(array('guid' => $TEST_GUID));
    fail( 'still there after delete!' );
}
catch (Exception $ex) {
    is( $ex->getCode(), 404, 'deleted 404 - exception thrown' );
}
