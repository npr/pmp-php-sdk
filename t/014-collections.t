#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// search and follow collection links
//

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(14);
ok( $sdk = new \Pmp\Sdk($host, $client_id, $client_secret), 'instantiate new Sdk' );

// doc with a bunch of collection links/rels
$TEST_DOC = array(
    'attributes' => array(
        'guid'  => 'do-not-actually-save-this-doc',
        'title' => 'PMP PHP SDK Test Document',
        'tags'  => array('pmp_php_sdk_test_doc'),
    ),
    'links' => array(
        'collection' => array(
            array(
                'href' => "$host/topics/arts",
                'rels' => array('urn:collectiondoc:collection:property'),
            ),
            array(
                'href' => "$host/topics/food",
                'rels' => array('urn:collectiondoc:collection:series'),
            ),
            array(
                'href' => "$host/topics/health",
                'rels' => array('urn:collectiondoc:collection:topic'),
            ),
            array(
                'href' => "$host/topics/education",
                'rels' => array('urn:collectiondoc:collection:topic'),
            ),
            array(
                'href' => "$host/topics/foobar",
            ),
        ),
    ),
);
ok( $doc = $sdk->newDoc('story', $TEST_DOC), 'create doc - new' );
is( $doc->href, null, 'create doc - href' );
is( $doc->getProfileAlias(), 'story', 'create doc - profile' );

// all collection links
is( $doc->links('collection')->count(), 5, 'collection links - count' );
is( $doc->links('collection', 'urn:collectiondoc:collection:topic')->count(), 2, 'collection links - count with urn' );
is( $doc->links('collection', 'nothingz')->count(), 0, 'collection links - count with bad urn' );

// collection shortcut
is( $doc->getCollections()->count(), 5, 'collection shortcut - count' );
is( $doc->getCollections('urn:collectiondoc:collection:topic')->count(), 2, 'collection shortcut - topic by urn' );
is( $doc->getCollections('topic')->count(), 2, 'collection shortcut - topic by alias' );
is( $doc->getCollections('series')->count(), 1, 'collection shortcut - series' );
is( $doc->getCollections('property')->count(), 1, 'collection shortcut - property' );

// follow a shortcut
$series = $doc->getCollections('series')->first()->follow();
is( $series->attributes->title, 'Food', 'follow series - title' );
is( $series->getProfileAlias(), 'topic', 'follow series - profile' );
