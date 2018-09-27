<?php
require_once dirname(__FILE__) . '/build/Burgomaster.php';

//
// use burgomaster to package phar/zip files
// (https://github.com/mtdowling/Burgomaster)
//
// the "make build" command should have curl'd the file
//

$staging  = dirname(__FILE__) . '/build/staging';
$root     = dirname(__FILE__);
$packager = new \Burgomaster($staging, $root);

// basic text files
foreach (['README.md', 'LICENSE'] as $file) {
    $packager->deepCopy($file, $file);
}

// copy pmp core
$packager->recursiveCopy('src/Pmp', 'Pmp');

// copy vendor'd libs
$packager->recursiveCopy('vendor/guzzlehttp/guzzle/src', 'GuzzleHttp');
$packager->recursiveCopy('vendor/guzzlehttp/promises/src', 'GuzzleHttp/Promise');
$packager->recursiveCopy('vendor/guzzlehttp/psr7/src', 'GuzzleHttp/Psr7');
$packager->recursiveCopy('vendor/psr/http-message/src', 'Psr/Http/Message');

// autoload the PMP entry point and function files
$packager->createAutoloader([
    'Pmp/Sdk.php',
    'GuzzleHttp/functions_include.php',
    'GuzzleHttp/Psr7/functions_include.php',
    'GuzzleHttp/Promise/functions_include.php',
]);

// create archive
$packager->createPhar("$root/build/pmpsdk.phar");
