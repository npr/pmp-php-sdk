#!/usr/bin/env php
<?php
require_once 'Common.php';

use \Pmp\Sdk as Sdk;
use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\Exception\AuthException as AuthException;
use \Pmp\Sdk\Exception\HostException as HostException;

//
// Make sure token-auth is working as it should
//

$TEST_LABEL = 'pmp-php-sdk-test';
$ARTS_TOPIC = '89944632-fe7c-47df-bc2c-b2036d823f98';
$SHORT_TIMEOUT = 4;
$SLEEP_TIME = 1;

// plan and connect
list($host, $id, $secret) = pmp_client_plan(8);
ok( $auth = new AuthClient($host, $id, $secret), 'instantiate new AuthUser' );
ok( $sdk = new Sdk($host, $id, $secret), 'instantiate new Sdk' );

// request a document via the SDK
ok( $doc = $sdk->fetchDoc($ARTS_TOPIC), 'fetch doc with token' );

// expire the token, without telling the SDK about it
ok( $revoke = $auth->revokeToken(), 'revoke token' );
sleep(2);

// request again - sdk should retry the 401 with a new token
ok( $doc = $sdk->fetchDoc($ARTS_TOPIC), 'fetch doc with new token' );

// invalid host
try {
    $bad_host = new Sdk('https://api-foobar.pmp.io', $id, $secret);
    fail( 'invalid host - no exception' );
}
catch (HostException $e) {
    pass( 'invalid host - throws exception' );
}

// invalid url
try {
    $bad_path = new Sdk($host . '/1234', $id, $secret);
    fail( 'invalid host path - no exception' );
}
catch (HostException $e) {
    is( $e->getCode(), 404, 'invalid host path - throws host exception' );
}

// invalid client
try {
    $bad_client = new Sdk($host, $id, 'foobar');
    fail( 'invalid client - no exception' );
}
catch (AuthException $e) {
    is( $e->getCode(), 401, 'invalid client - throws exception' );
}
