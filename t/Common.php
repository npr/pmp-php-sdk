<?php
error_reporting(E_ALL);

require_once 'Test.php';
require_once 'src/Pmp/Sdk.php';

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
    return pmp_plan($num_tests, array('PMP_HOST', 'PMP_USERNAME', 'PMP_PASSWORD'));
}

/**
 * Plan running client tests
 *
 * @param int $num_tests the number of tests to plan for
 * @return array an array containing [$host, $id, $secret]
 */
function pmp_client_plan($num_tests) {
    return pmp_plan($num_tests, array('PMP_HOST', 'PMP_CLIENT_ID', 'PMP_CLIENT_SECRET'));
}

/**
 * Plan running client tests
 *
 * @param int $num_tests the number of tests to plan for
 * @return array an array containing [$host, $id, $secret]
 */
function pmp_both_plan($num_tests) {
    return pmp_plan($num_tests, array('PMP_HOST', 'PMP_USERNAME', 'PMP_PASSWORD', 'PMP_CLIENT_ID', 'PMP_CLIENT_SECRET'));
}

/**
 * Planning helper, to require env variables
 *
 * @param int $num_tests the number of tests to plan for
 * @param array $req_envs the env variables to require
 * @return array an array containing the required env variables
 */
function pmp_plan($num_tests, $req_envs = array()) {
    $vars = array();
    $missing = array();
    foreach ($req_envs as $name) {
        if (getenv($name)) {
            $vars[] = getenv($name);
        }
        else {
            $vars[] = null;
            $missing[] = $name;
        }
    }

    // plan it
    if (empty($missing)) {
        plan($num_tests);
    }
    else {
        $missing = join(', ', $missing);
        plan('skip_all', 'missing required PMP env variables: ' . $missing);
    }
    return $vars;
}
