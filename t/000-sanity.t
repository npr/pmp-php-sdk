#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\AuthUser   as AuthUser;

//
// Just make sure that things initialize correctly, and that we have all the
// env variables necessary to run the tests
//

list($host, $user, $pass, $id, $secret) = pmp_both_plan(6);

// check user connection
ok( $user = new AuthUser($host, $user, $pass), 'instantiate new AuthUser' );
ok( $list = $user->listCredentials(), 'list user credentials' );

// check client connection
ok( $auth = new AuthClient($host, $id, $secret), 'instantiate new AuthClient' );
ok( $token = $auth->getToken(), 'get access token' );

// check sdk connection
ok( $sdk = new \Pmp\Sdk($host, $id, $secret), 'instantiate new Sdk' );
ok( $sdk->home, 'sdk home doc' );
