#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// crud a doc via the sdk
//

$TEST_GUID = '8def7ee1-6a9d-407d-b269-538bb454ad1e';
$TEST_DOC = array(
    'attributes' => array(
        'guid'  => $TEST_GUID,
        'title' => 'PMP PHP SDK Test Document',
        'tags'  => array('pmp_php_sdk_test_doc'),
    ),
);

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(24);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// 1) create
ok( $doc = $sdk->newDoc('story', $TEST_DOC), 'create doc - new' );
is( $doc->href, null, 'create doc - href' );
is( $doc->attributes->guid, $TEST_GUID, 'create doc - guid' );
like( $doc->getProfile()->href, '#profiles/story$#', 'create doc - profile' );
try {
    $doc->save();
    pass( 'create - ok' );
}
catch (Exception $e) {
    fail( "unable to create document: $e" );
}
isnt( $doc->href, null, 'create doc - href gets set' );
is( $doc->scope, 'write', 'create doc - scope gets set' );

// 2) read
ok( $fetched = $sdk->fetchDoc($TEST_GUID), 'read by guid' );
like( $fetched->href, "#docs/$TEST_GUID#", 'read by guid - href' );
is( $fetched->attributes->guid, $TEST_GUID, 'read by guid - guid' );
is( $fetched->attributes->title, 'PMP PHP SDK Test Document', 'read by guid - title' );
is( $fetched->scope, 'write', 'read by guid - scope' );
like( $fetched->getProfile()->href, '#profiles/story$#', 'read by guid - profile link' );

// 3) update
try {
    $doc->attributes->title = 'zzz PMP PHP SDK Test Doc';
    $doc->save();
    pass( 'update - ok' );
}
catch (Exception $e) {
    fail( "unable to update document: $e" );
}

// 3.25) update with invalid data
try {
    $doc->links->profile[0]->href = "$host/profiles/foobar";
    $doc->save();
    fail( 'bad update - did not throw exception' );
    fail( 'bad update - no validation message' );
}
catch (\Pmp\Sdk\Exception\ValidationException $e) {
    is( $e->getCode(), 400, 'bad update - got 400' );
    like( $e->getValidationMessage(), '/invalid #\/links\/profile provided/i', 'bad update - validation message');
}

// 3.5) re-read after update
ok( $fetched = $sdk->fetchDoc($TEST_GUID), 'upread by guid' );
like( $fetched->href, "#docs/$TEST_GUID#", 'upread by guid - href' );
is( $fetched->attributes->guid, $TEST_GUID, 'upread by guid - guid' );
is( $fetched->attributes->title, 'zzz PMP PHP SDK Test Doc', 'upread by guid - title' );
like( $fetched->getProfile()->href, '#profiles/story$#', 'upread by guid - profile link' );

// 4) delete
try {
    $doc->delete();
    pass( 'delete - ok' );
}
catch (Exception $ex) {
    fail( "unable to delete document: $ex" );
}

// 4.5) re-read after delete
is( $sdk->fetchDoc($TEST_GUID), null, 'deleted 404 - returns null' );
