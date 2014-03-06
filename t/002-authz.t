#!/usr/bin/env php
<?php
require_once 'Test.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';
require_once 'lib/Pmp/Sdk/CollectionDocJson.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;
use \Pmp\Sdk\Exception as Exception;

if (getenv('PMP_CLIENT_ID') && getenv('PMP_CLIENT_SECRET')) {
    plan(30);
}
else {
    plan('skip_all', 'set PMP_CLIENT_ID and PMP_CLIENT_SECRET to run server tests');
}

$host = 'https://api-sandbox.pmp.io';
$client_id = getenv('PMP_CLIENT_ID');
$client_secret = getenv('PMP_CLIENT_SECRET');

ok( $auth = new AuthClient($host, $client_id, $client_secret),
    "instantiate new AuthClient");

if ($auth->getToken()->token_expires_in < 10) {
    die("Access token expires too soon. Not enough time to make a request. Mayday, mayday");
}

clean_up_test_docs($host, $auth);
if ( getenv('PMP_CLIENT_CLEAN') ) {
    diag("PMP_CLIENT_CLEAN found. exiting...");
    exit(0);
}

// create 3 orgs
$org1_pass = '6wPxgpbtZeqW1234';
$org2_pass = '6wPxgpbtZeqW5678';
$org3_pass = '6wPxgpbtZeqW9012';
ok(  $org1 = save_doc($host, $auth, 'user', array( 'attributes' => array(
                'tags'  => array('pmp_sdk_php_test_authz'),
                'title' => 'pmp_sdk_php test org1',
                'auth'  => array(
                    'user'     => 'pmp_sdk_php-org1',
                    'password' => $org1_pass,
                ),
            ),
        )
    ), "create org1"
);
ok( $org2 = save_doc($host, $auth, 'user',  array( 'attributes' => array(
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
                'title' => 'pmp_sdk_php test org2',
                'auth'  => array(
                    'user'     => 'pmp_sdk_php-org2',
                    'password' => $org2_pass,
                ),
            ),
        )
    ), "create org2"
);
ok( $org3 = save_doc($host, $auth, 'user', array( 'attributes' => array(
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
                'title' => 'pmp_sdk_php test org3',
                'auth'  => array(
                    'user'     => 'pmp_sdk_php-org3',
                    'password' => $org3_pass,
                ),
            ),
        )
    ), "create org3"
);

// 4 groups, in different combinations of orgs
ok( $group1 = save_doc($host, $auth, 'group', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php permission group1',
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
            ),
            'links' => array(
                'item' => array(
                    array('href' => $org1->getUri()),
                    array('href' => $org2->getUri()),
                )
            ),
        )
    ), "create group1"
);
ok( $group2 = save_doc($host, $auth, 'group', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php permission group2',
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
            ),
            'links' => array(
                'item' => array(
                    array('href' => $org1->getUri()),
                )
            ),
        )
    ), "create group2"
);
ok( $group3 = save_doc($host, $auth, 'group', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php permission group3',
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
            ),
            'links' => array(
                'item' => array(
                    array('href' => $org2->getUri()),
                )
            ),
        )
    ), "create group3"
);
ok( $empty_group = save_doc($host, $auth, 'group', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php permission group empty',
                'tags'  => array( 'pmp_sdk_php_test_authz' ),
            ),
        )
    ), "create empty group"
);

// stories with permission combinations
// allow group1 (org1 or org2)
ok( $story1 = save_doc($host, $auth, 'story', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php i am test document one',
                'tags'  => array('pmp_sdk_php_test_authz', 'pmp_sdk_php_test_doc'),
            ),
            'links' => array(
                'permission' => array(
                    array('href' => $group1->getUri(), 'operation' => 'read')
                )
            ),
        )
    ), "create story1"
);
// disallow group3 (org2), allow group2 (org1)
ok( $story2 = save_doc($host, $auth, 'story', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php i am test document two',
                'tags'  => array('pmp_sdk_php_test_authz', 'pmp_sdk_php_test_doc'),
            ),
            'links' => array(
                'permission' => array(
                    array('href' => $group3->getUri(), 'operation' => 'read', 'blacklist' => true),
                    array('href' => $group2->getUri(), 'operation' => 'read')
                )
            ),
        )
    ), "create story2"
);
// no permissions, open to the world
ok( $story3 = save_doc($host, $auth, 'story', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php i am test document three',
                'tags'  => array('pmp_sdk_php_test_authz', 'pmp_sdk_php_test_doc'),
            )
        )
    ), "create story3"
);
// private story should only be visible to the creator
ok( $story_private = save_doc($host, $auth, 'story', array(
            'attributes' => array(
                'title' => 'pmp_sdk_php i am test document private',
                'tags'  => array('pmp_sdk_php_test_authz', 'pmp_sdk_php_test_doc'),
            ),
            'links' => array(
                'permission' => array(
                    array('href' => $empty_group->getUri(), 'operation' => 'read')
                )
            ),
        )
    ), "create story_private"
);

// fixtures all done.

// create credentials and run the actual authz tests.
$credentials_uri = AuthClient::getCredentialsURI($host);
ok( $org1_creds = AuthClient::createCredentials(array(
            'uri'      => $credentials_uri,
            'username' => $org1->attributes->auth->user,
            'password' => $org1_pass,
        )),
    "create org1 credentials"
);
ok( $org2_creds = AuthClient::createCredentials(array(
            'uri'      => $credentials_uri,
            'username' => $org2->attributes->auth->user,
            'password' => $org2_pass,
        )),
    "create org2 credentials"
);
ok( $org3_creds = AuthClient::createCredentials(array(
            'uri'      => $credentials_uri,
            'username' => $org3->attributes->auth->user,
            'password' => $org3_pass,
        )),
    "create org3 credentials"
);

sleep(10);    // give 202 responses time to catch up

ok( $org1_client = new AuthClient($host, $org1_creds->client_id, $org1_creds->client_secret),
    "create org1 client");
ok( $org2_client = new AuthClient($host, $org2_creds->client_id, $org2_creds->client_secret),
    "create org2 client");
ok( $org3_client = new AuthClient($host, $org3_creds->client_id, $org3_creds->client_secret),
    "create org3 client");

// org1 should see doc1, doc2, doc3
// org2 should see doc1, doc3
// org3 should see doc3
ok( $org1_res = CollectionDocJson::search($host, $org1_client, array( 'tag' => 'pmp_sdk_php_test_doc' )),
    "org1 search"
);
ok(  $org2_res = CollectionDocJson::search($host, $org2_client, array( 'tag' => 'pmp_sdk_php_test_doc' )),
    "org2 search"
);
ok(  $org3_res = CollectionDocJson::search($host, $org3_client, array( 'tag' => 'pmp_sdk_php_test_doc' )),
    "org3 search"
);

diag('org1_res');
diag_search_results($org1_res);
diag('org2_res');
diag_search_results($org2_res);
diag('org3_res');
diag_search_results($org3_res);

// basic count check
is( count($org1_res->items()->toArray()), 3, "org1 has 3 items" );
is( count($org2_res->items()->toArray()), 2, "org2 has 2 items" );
is( count($org3_res->items()->toArray()), 1, "org3 has 1 item" );

// specific title checks
foreach ($org1_res->items()->toArray() as $r) {
    $title = $r->attributes->title;
    like($title, '/i am test document (one|two|three)$/', "org1 has doc $title");
}
foreach ($org2_res->items()->toArray() as $r) {
    $title = $r->attributes->title;
    like($title, '/i am test document (one|three)$/', "org2 has doc $title");
}
foreach ($org3_res->items()->toArray() as $r) {
    $title = $r->attributes->title;
    like($title, '/i am test document three$/', "org3 has doc $title");
}

// all done
clean_up_test_docs($host, $auth);

/*************************************************************************/
// helper functions


/**
 * save array structure via CollectionDocJson
 *
 * @param string  $host
 * @param AuthClient $auth
 * @param string  $profile
 * @param array   $attr
 * @return unknown
 */
function save_doc($host, $auth, $profile, $attr) {

    // generic object
    $doc = new \stdClass;
    $doc->version = '1.0';
    // hack to turn array into object
    $doc->attributes = json_decode(json_encode($attr['attributes']));
    $profile_link = new \stdClass;
    $profile_link->href = $host . '/profiles/' . $profile;
    $doc->links->profile[0] = $profile_link;
    if (isset($attr['links'])) {
        foreach ($attr['links'] as $key=>$val) {
            $doc->links->$key = json_decode(json_encode($val));
        }
    }

    // pmp doc object
    $cdj = null;
    try {
        $cdj = new CollectionDocJson($host, $auth);
        $cdj->setDocument($doc);
        $cdj->save();
    }
    catch (Exception $ex) {
        die( "Failed to save doc with attributes: " . var_export($attr, true) . "\n$ex\n" );
    }

    return $cdj;
}


/**
 * clean up any test documents
 *
 * @param string  $host
 * @param PmpSdkAuthClient $auth
 */
function clean_up_test_docs($host, $auth) {
    $urn_docs = 'urn:collectiondoc:query:docs';
    $profiles = array('story', 'organization', 'user', 'group');
    foreach ($profiles as $profile) {
        $options = array('profile' => $profile, 'text' => 'pmp_sdk_php', 'limit' => 100, );
        $results = CollectionDocJson::search($host, $auth, $options);
        if ($results) {
            foreach ($results->items()->toArray() as $item) {
                $doc = new CollectionDocJson($host, $auth);
                $doc->setDocument($item);
                $uri = $doc->getSaveUri();
                diag( "cleaning up $profile $uri" );
                $doc->delete();
            }
        }
        diag("finished clean-up check for $profile");
    }
    diag("clean-up complete");
}


/**
 *
 *
 * @param unknown $results
 */
function diag_search_results($results) {
    foreach ($results->items()->toArray() as $r) {
        diag(sprintf("result: %s [%s]", $r->attributes->title, $r->attributes->guid));
    }
}
