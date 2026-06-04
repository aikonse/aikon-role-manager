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

// Security keys and salts — fixed values are fine for a test environment.
define('AUTH_KEY', 'test-auth-key');
define('SECURE_AUTH_KEY', 'test-secure-auth-key');
define('LOGGED_IN_KEY', 'test-logged-in-key');
define('NONCE_KEY', 'test-nonce-key');
define('AUTH_SALT', 'test-auth-salt');
define('SECURE_AUTH_SALT', 'test-secure-auth-salt');
define('LOGGED_IN_SALT', 'test-logged-in-salt');
define('NONCE_SALT', 'test-nonce-salt');

define('WP_TESTS_DOMAIN', 'localhost');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');
define('WPLANG', '');
