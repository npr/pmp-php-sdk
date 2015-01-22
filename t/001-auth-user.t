#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk\AuthUser as AuthUser;
use \Pmp\Sdk\Exception\AuthException as AuthException;
use \Pmp\Sdk\Exception\HostException as HostException;

//
// Test out the AuthUser class (generating clients)
//

$TEST_LABEL = 'pmp-php-sdk-test';

// plan and connect
list($host, $username, $password) = pmp_user_plan(21);
ok( $user = new AuthUser($host, $username, $password), 'instantiate new AuthUser' );

// create a credential
ok( $cred = $user->createCredential('read', 999, $TEST_LABEL), 'create cred' );
ok( $cred->client_id, 'create cred - id' );
ok( $cred->client_secret, 'create cred - secret' );
is( $cred->token_expires_in, 999, 'create cred - expires' );
is( $cred->scope, 'read', 'create cred - scope' );
is( $cred->label, $TEST_LABEL, 'create cred - label' );
sleep(2); // wait for server to catch up, just in case

// list credentials
ok( $list = $user->listCredentials(), 'list creds' );
ok( count($list->clients) > 0, 'list creds - count' );

// search for cred
$my_cred = false;
foreach ($list->clients as $list_client) {
    if ($list_client->client_id == $cred->client_id) {
        $my_cred = $list_client;
    }
}
ok( $my_cred, 'list creds - found new' );
is( $my_cred->client_id, $cred->client_id, 'list creds - id' );
is( $my_cred->client_secret, $cred->client_secret, 'list creds - secret' );
is( $my_cred->token_expires_in, 999, 'list creds - expires' );
is( $my_cred->scope, 'read', 'list creds - scope' );
is( $my_cred->label, $TEST_LABEL, 'list creds - label' );

// remove credential
is( $user->removeCredential($cred->client_id), true, 'remove cred' );
is( $user->removeCredential('foobar'), true, 'remove non-existent cred' );
ok( $relist = $user->listCredentials(), 'remove cred - relist' );

// search for removed cred
$found_cred = false;
foreach ($relist->clients as $list_client) {
    if ($list_client->client_id == $cred->client_id) {
        $found_cred = $list_client;
    }
}
ok( !$found_cred, 'remove cred - no longer in list' );

// invalid host
try {
    $bad_host = new AuthUser('https://api-foobar.pmp.io', $username, $password);
    $bad_host->listCredentials();
    fail( 'invalid host - no exception' );
}
catch (HostException $e) {
    pass( 'invalid host - throws exception' );
}

// invalid password
try {
    $bad_user = new AuthUser($host, $username, 'foobar');
    $bad_user->listCredentials();
    fail( 'invalid password - no exception' );
}
catch (AuthException $e) {
    pass( 'invalid password - throws exception' );
}

// cleanup
foreach ($relist->clients as $list_client) {
  if ($list_client->label == $TEST_LABEL) {
    $user->removeCredential($list_client->client_id);
  }
}
