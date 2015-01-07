<?php
require_once 'Test.php';

//
// Common utilities for PMP tests
//

/**
 * Plan running user tests
 *
 * @param int $num_tests the number of tests to plan for
 * @return array an array containing [$host, $user, $pass]
 */
function pmp_user_plan($num_tests) {
    $host = getenv('PMP_HOST');
    $user = getenv('PMP_USERNAME');
    $pass = getenv('PMP_PASSWORD');
    if ($host && $user && $pass) {
        plan($num_tests);
    }
    else {
        plan('skip_all', 'missing required envs PMP_HOST, PMP_USERNAME, PMP_PASSWORD');
    }
    return Array($host, $user, $pass);
}

/**
 * Plan running client tests
 *
 * @param int $num_tests the number of tests to plan for
 * @return array an array containing [$host, $id, $secret]
 */
function pmp_client_plan($num_tests) {
    $host   = getenv('PMP_HOST');
    $id     = getenv('PMP_CLIENT_ID');
    $secret = getenv('PMP_CLIENT_SECRET');
    if ($host && $id && $secret) {
        plan($num_tests);
    }
    else {
        plan('skip_all', 'missing required envs PMP_HOST, PMP_CLIENT_ID, PMP_CLIENT_SECRET');
    }
    return Array($host, $id, $secret);
}
