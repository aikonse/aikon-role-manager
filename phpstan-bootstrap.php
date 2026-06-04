<?php

/**
 * PHPStan bootstrap — declares WordPress runtime constants that are
 * site-specific (defined in wp-config.php) and therefore unknown to
 * static analysis.
 */

if (!defined('AUTH_SALT')) {
    define('AUTH_SALT', '');
}

if (!defined('COOKIEPATH')) {
    define('COOKIEPATH', '/');
}

if (!defined('COOKIE_DOMAIN')) {
    define('COOKIE_DOMAIN', '');
}
