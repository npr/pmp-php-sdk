#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// test creating/editing document links
//

$TEST_GUID  = '8def7ee1-6a9d-407d-b269-538bb454ad1e';
$TEST_GUID2 = '0260c04a-2d3b-43df-b1b2-e9b79cc8da43';
$TEST_DOC = array(
    'attributes' => array(
        'guid'  => $TEST_GUID,
        'title' => 'PMP PHP SDK Test Document',
        'tags'  => array('pmp_php_sdk_test_doc'),
    ),
);
$TEST_IMG = array(
    'attributes' => array(
        'guid'  => $TEST_GUID2,
        'title' => 'PMP PHP SDK Test Image',
        'tags'  => array('pmp_php_sdk_test_doc'),
    ),
    'links' => array(
        'enclosure' => array(
            array(
                'href' => 'https://support.pmp.io',
                'type' => 'image/jpeg',
                'meta' => array('crop' => 'primary'),
            ),
        ),
    ),
);

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(26);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// init story
ok( $doc = $sdk->newDoc('story', $TEST_DOC), 'init doc - new' );
is( $doc->href, null, 'init doc - href' );
is( $doc->attributes->guid, $TEST_GUID, 'init doc - guid' );

// alt link
is( $doc->links('alternate')->count(), 0, 'init doc - alt links empty' );
$doc->links->alternate = array();
$doc->links->alternate[] = (object) array('href' => 'https://api.pmp.io', 'title' => 'foobar');
is( $doc->links('alternate')->count(), 1, 'init doc - set alt link' );
is( $doc->links('alternate')->first()->title, 'foobar', 'init doc - alt link title' );
is( $doc->links('alternate')->first()->href, 'https://api.pmp.io', 'init doc - alt link href' );

// init image
ok( $img = $sdk->newDoc('image', $TEST_IMG), 'init image - new' );
is( $img->href, null, 'init image - href' );
is( $img->attributes->guid, $TEST_GUID2, 'init image - guid' );
is( $img->links('enclosure')->count(), 1, 'init image - enclosures' );
is( $img->links('enclosure')->first()->href, 'https://support.pmp.io', 'init image - enclosure href' );
is( $img->links('enclosure')->first()->type, 'image/jpeg', 'init image - enclosure type' );
is( $img->links('enclosure')->first()->meta->crop, 'primary', 'init image - enclosure crop' );

// save image
try {
    $img->save();
    pass( 'create image - ok' );
}
catch (\Pmp\Sdk\Exception\RemoteException $e) {
    fail( "unable to create image: $e" );
}
like( $img->href, "#docs/$TEST_GUID2#", 'create image - href set' );

// attach and save story
try {
    $doc->links->item = array(new \stdClass());
    $doc->links->item[0]->href = $img->href;
    $doc->save();
    pass( 'create story - ok' );
}
catch (\Pmp\Sdk\Exception\RemoteException $e) {
    fail( "unable to create story: $e" );
}

// check the re-loaded story
like( $doc->href, "#docs/$TEST_GUID#", 'create story - href set' );
is( $doc->links('alternate')->count(), 1, 'create story - alt link' );
is( $doc->links('alternate')->first()->title, 'foobar', 'create story - alt link title' );
is( $doc->links('alternate')->first()->href, 'https://api.pmp.io', 'create story - alt link href' );
is( $doc->items()->count(), 1, 'create story - items count' );
is( $doc->items()->first()->attributes->guid, $TEST_GUID2, 'create story - items guid' );

// cleanup!
try {
    $doc->delete();
    pass( 'delete story - ok' );
}
catch (Exception $e) {
    fail( "unable to delete story: $e" );
}
try {
    $img->delete();
    pass( 'delete image - ok' );
}
catch (Exception $e) {
    fail( "unable to delete image: $e" );
}
