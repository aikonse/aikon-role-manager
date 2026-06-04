<?php

/**
 * WordPress test configuration for wp-env.
 *
 * These credentials match the wp-env tests-cli Docker environment.
 * Do not use in production.
 */

define('ABSPATH', '/var/www/html/');

define('DB_NAME', 'tests-wordpress');
define('DB_USER', 'root');
define('DB_PASSWORD', 'password');
define('DB_HOST', 'mysql');

$table_prefix = 'wptests_';

define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');
define('WPLANG', '');
