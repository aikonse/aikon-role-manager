<?php

/**
 * Bootstrap for integration tests.
 *
 * Requires a running wp-env tests environment.
 * Run via: npm run test:integration
 */

$_tests_dir = getenv('WP_TESTS_DIR') ?: '/wordpress-phpunit';

if (! file_exists($_tests_dir . '/includes/functions.php')) {
    throw new RuntimeException(
        "WordPress test suite not found at {$_tests_dir}.\n" .
        "Make sure wp-env is running: npm run wp:start\n"
    );
}

// Point the WP test bootstrap to our config file
define('WP_TESTS_CONFIG_FILE_PATH', dirname(__DIR__, 2) . '/wp-tests-config.php');

// Load WP test functions (must come before the bootstrap)
require_once $_tests_dir . '/includes/functions.php';

// Load the plugin during muplugins_loaded so it boots inside WordPress
function _aikon_role_manager_load(): void
{
    require_once dirname(__DIR__, 2) . '/aikon-role-manager.php';
}
tests_add_filter('muplugins_loaded', '_aikon_role_manager_load');

// Boot WordPress
require_once $_tests_dir . '/includes/bootstrap.php';
