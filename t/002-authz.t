#!/usr/bin/env php
<?php
require_once 'Test.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';
require_once 'lib/Pmp/Sdk/CollectionDocJson.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\CollectionDocJson as CollectionDocJson;
use \Pmp\Sdk\Exception as Exception;

if (getenv('PMP_CLIENT_ID') && getenv('PMP_CLIENT_SECRET')) {
    plan(2);
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


// all done
clean_up_test_docs($host, $auth);


/**
 * helper functions
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

    // pmp doc object
    try {
        $client = new CollectionDocJson($host, $auth);
        $client->setDocument($doc);
        $client->save();
    }
    catch (Exception $ex) {
        die( "Failed to save doc with attributes: " . var_export($attr, true) . "\n$ex\n" );
    }

    return $client;
}


/**
 * clean up any test documents
 *
 * @param string  $host
 * @param PmpSdkAuthClient $auth
 */
function clean_up_test_docs($host, $auth) {
    $urn_docs = 'urn:pmp:query:docs';
    $profiles = array('story', 'organization', 'user', 'group');
    foreach ($profiles as $profile) {
        try {
            $authz_test = new CollectionDocJson($host, $auth);
        } catch (Exception $ex) {
            diag($ex->getMessage());
            exit(1);
        }
        $options = array('profile' => $profile, 'text' => 'pmp_sdk_php', 'limit' => 100, );
        $results = null;
        try {
            $results = $authz_test->query($urn_docs)->submit($options);
        } catch (Exception $ex) {
            // 404 throws an exception. seems pretty unfriendly
            // for a search, which can easily have no results
            if (!preg_match('/^Got unexpected non-HTTP-200 response/', $ex->getMessage())) {
                die("$ex");
            }
        }
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