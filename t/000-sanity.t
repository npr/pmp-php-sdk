#!/usr/bin/env php
<?php
require_once 'Test.php';
require_once 'lib/Pmp/Sdk/AuthClient.php';
require_once 'lib/Pmp/Sdk/AuthUser.php';

use \Pmp\Sdk\AuthClient as AuthClient;
use \Pmp\Sdk\AuthUser   as AuthUser;

// init manually (check both user and client auth here)
$host   = getenv('PMP_HOST');
$user   = getenv('PMP_USERNAME');
$pass   = getenv('PMP_PASSWORD');
$id     = getenv('PMP_CLIENT_ID');
$secret = getenv('PMP_CLIENT_SECRET');
if (!$host || !$user || !$pass || !$id || !$secret) {
  $missing = Array(
    $host   ? null : 'PMP_HOST',
    $user   ? null : 'PMP_USERNAME',
    $pass   ? null : 'PMP_PASSWORD',
    $id     ? null : 'PMP_CLIENT_ID',
    $secret ? null : 'PMP_CLIENT_SECRET'
  );
  $missing = join(', ', array_filter($missing));
  plan('skip_all', 'missing required PMP env variables: ' . $missing);
}
else {
  plan(2);
}

// check user connection
ok( $user = new AuthUser($host, $user, $pass), 'instantiate new AuthUser' );
ok( $list = $user->listCredentials(), 'list user credentials' );

// check client connection
ok( $auth = new AuthClient($host, $id, $secret), 'instantiate new AuthClient' );
ok( $token = $auth->getToken(), 'get access token' );
