#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// serialization of the sdk
//

// plan and connect
list($host, $id, $secret) = pmp_client_plan(14);
ok( $sdk = new \Pmp\Sdk($host, $id, $secret), 'instantiate new Sdk' );

// serialize
ok( $serial = serialize($sdk),           'normal - serialize Sdk' );
ok( $new_sdk = unserialize($serial),     'normal - unserialize Sdk' );
ok( $doc = $new_sdk->fetchTopic('arts'), 'normal - fetch doc' );

// invalid string (turn off notices)
$str2 = substr($serial, 0, 500) . 'foobar' . substr($serial, 500);
//$level = error_reporting(E_ALL & ~E_NOTICE);
try {
    $bad_sdk = unserialize($str2);
    fail( 'invalid - no exception' );
}
catch (\RuntimeException $e) {
    pass( 'invalid - throws runtime exception' );
}
//error_reporting($level);

// expired token
ok( $auth = new \Pmp\Sdk\AuthClient($host, $id, $secret), 'expired - get new AuthUser' );
ok( $revoke = $auth->revokeToken(),                       'expired - revoke token' );
ok( $exp_sdk = unserialize($serial),                      'expired - unserialize Sdk' );
ok( $doc = $exp_sdk->fetchTopic('arts'),                  'expired - fetch doc' );

// zipped serialization
ok( $sdk = new \Pmp\Sdk($host, $id, $secret, array('serialzip' => true)), 'instantiate new serialzip Sdk' );

// serialize and compare it
ok( $serialzip = serialize($sdk),                 'zipped - serialize Sdk' );
cmp_ok( strlen($serialzip), '<', strlen($serial), 'zipped - is pretty small' );
ok( $zip_sdk = unserialize($serialzip),           'zipped - unserialize Sdk' );
ok( $doc = $zip_sdk->fetchTopic('arts'),          'zipped - fetch doc' );
