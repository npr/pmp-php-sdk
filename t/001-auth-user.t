#!/usr/bin/env php
<?php
require_once 'Common.php';
require_once 'lib/Pmp/Sdk/AuthUser.php';

use \Pmp\Sdk\AuthUser as AuthUser;

$TEST_LABEL = 'pmp-php-sdk-test';

// plan and connect
list($host, $user, $pass) = pmp_user_plan(18);
ok( $user = new AuthUser($host, $user, $pass), 'instantiate new AuthUser' );

// create a credential
ok( $cred = $user->createCredential('read', 999, $TEST_LABEL), 'create cred' );
ok( $cred->client_id, 'create cred - id' );
ok( $cred->client_secret, 'create cred - secret' );
is( $cred->token_expires_in, 999, 'create cred - expires' );
is( $cred->scope, 'read', 'create cred - scope' );
is( $cred->label, $TEST_LABEL, 'create cred - label' );

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
ok( $relist = $user->listCredentials(), 'remove cred - relist' );

// search for removed cred
$found_cred = false;
foreach ($relist->clients as $list_client) {
  if ($list_client->client_id == $cred->client_id) {
    $found_cred = $list_client;
  }
}
ok( !$found_cred, 'remove cred - no longer in list' );

// cleanup
foreach ($relist->clients as $list_client) {
  if ($list_client->label == $TEST_LABEL) {
    $user->removeCredential($list_client->client_id);
  }
}
