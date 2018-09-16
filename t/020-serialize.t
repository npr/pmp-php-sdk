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
$level = error_reporting(E_ALL & ~E_NOTICE);
$bad_serial = substr($serial, 0, 500) . 'foobar' . substr($serial, 500);
$bad_sdk = unserialize($bad_serial);
if ($bad_sdk !== false) {
    fail('invalid - unserialized without failure');
} else {
    pass('invalid - failed to unserialize');
}
error_reporting($level);

// expired token
ok( $auth = new \Pmp\Sdk\AuthClient($host, $id, $secret), 'expired - get new AuthUser' );
ok( $revoke = $auth->revokeToken(),                       'expired - revoke token' );
ok( $exp_sdk = unserialize($serial),                      'expired - unserialize Sdk' );
ok( $doc = $exp_sdk->fetchTopic('arts'),                  'expired - fetch doc' );

// zipped serialization
ok( $sdk = new \Pmp\Sdk($host, $id, $secret, array('serialzip' => true)), 'instantiate new serialzip Sdk' );
ok( $serialzip = serialize($sdk),                 'zipped - serialize Sdk' );
cmp_ok( strlen($serialzip), '<', strlen($serial), 'zipped - is pretty small' );
ok( $zip_sdk = unserialize($serialzip),           'zipped - unserialize Sdk' );
ok( $doc = $zip_sdk->fetchTopic('arts'),          'zipped - fetch doc' );
