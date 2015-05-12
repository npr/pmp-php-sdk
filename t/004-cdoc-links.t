#!/usr/bin/env php
<?php
require_once 'Common.php';

//
// CollectionDocJsonLinks tests
//
plan(14);

// dummy templated link
$dummy = new \stdClass();
$dummy->{'href-template'} = 'https://foobar/docs{?writeable,profile}';
$dummy->{'href-vars'} = array(
  'writeable' => 'http://somewhere.something',
  'profile'   => 'http://somewhere.something',
);

// basic convert options
ok( $link = new \Pmp\Sdk\CollectionDocJsonLink($dummy), 'convert - init link' );
is( $link->expand(), 'https://foobar/docs', 'convert - no options' );
is( $link->expand(array('foo' => 'bar')), 'https://foobar/docs', 'convert - unknown options' );
is( $link->expand(array('writeable' => 'true')), 'https://foobar/docs?writeable=true', 'convert - string option' );
is( $link->expand(array('writeable' => null)), 'https://foobar/docs', 'convert - null option' );
is( $link->expand(array('writeable' => '')), 'https://foobar/docs?writeable=', 'convert - blank option' );
is( $link->expand(array('writeable' => true)), 'https://foobar/docs?writeable=true', 'convert - bool true' );
is( $link->expand(array('writeable' => false)), 'https://foobar/docs?writeable=false', 'convert - bool false' );

// array syntax convert options
is( $link->expand(array('profile' => array())), 'https://foobar/docs?profile=', 'convert - array empty' );
is( $link->expand(array('profile' => array('AND' => array()))), 'https://foobar/docs?profile=', 'convert - empty AND' );
is( $link->expand(array('profile' => array('OR' => array()))), 'https://foobar/docs?profile=', 'convert - empty OR' );
is( $link->expand(array('profile' => array('AND' => array('foo', 'bar')))), 'https://foobar/docs?profile=foo%2Cbar', 'convert - normal AND' );
is( $link->expand(array('profile' => array('OR' => array('foo', 'bar')))), 'https://foobar/docs?profile=foo%3Bbar', 'convert - normal OR' );
is( $link->expand(array('profile' => array('OR' => array('foo2', 'bar2'), 'AND' => array('foo', 'bar')))), 'https://foobar/docs?profile=foo%2Cbar', 'convert - AND and OR' );
