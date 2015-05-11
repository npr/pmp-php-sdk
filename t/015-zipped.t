#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// zipped and minimal response sizes
//

// plan and connect
list($host, $client_id, $client_secret) = pmp_client_plan(14);
function getSdk($gzip, $minimal) {
  global $host, $client_id, $client_secret;
  $opts = array('gzip' => $gzip, 'minimal' => $minimal);
  return new \Pmp\Sdk($host, $client_id, $client_secret, $opts);
}

// get the full response
ok( $sdk = getSdk(false, false), 'full response - get sdk' );
ok( $doc = $sdk->queryDocs(array('profile' => 'story')), 'full response - query stories' );
$full_json = json_decode($doc->_raw['body']);
$full_size = strlen($doc->_raw['body']);
ok( isset($doc->links->query), 'full response - inherits static link' );
ok( isset($full_json->links->query), 'full response - raw static link' );

// get minimal response
ok( $sdk = getSdk(false, true), 'min response - get sdk' );
ok( $doc = $sdk->queryDocs(array('profile' => 'story')), 'min response - query stories' );
$min_json = json_decode($doc->_raw['body']);
$min_size = strlen($doc->_raw['body']);
ok( isset($doc->links->query), 'min response - inherits static link' );
ok( !isset($min_json->links->query), 'min response - no raw static link' );
cmp_ok( $min_size, '<', $full_size, 'min response - smaller than full response' );

// get gzipped response
ok( $sdk = getSdk(true, true), 'gzip response - get sdk' );
ok( $doc = $sdk->queryDocs(array('profile' => 'story')), 'gzip response - query stories' );
$gzip_json = json_decode($doc->_raw['body']);
$gzip_size = strlen($doc->_raw['body']);
ok( isset($doc->links->query), 'gzip response - inherits static link' );
ok( !isset($gzip_json->links->query), 'gzip response - no raw static link' );
cmp_ok( $gzip_size, '<', $full_size, 'gzip response - smaller than full response' );
// TODO: how to compare pre-unzipped size?
