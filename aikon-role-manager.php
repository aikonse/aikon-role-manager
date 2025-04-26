<?php
/**
 * Plugin Name:			Aikon Role Manager
 * Plugin Url:			https://github.com/aikonse/role-manager
 * Description: 		Manage roles and permisisons, allow users multiple roles and gain control
 * Version:				1.0.0
 * Requires at least:   6.5
 * Tested up to:		6.8
 * Requires PHP:		8.0
 * Author:				Aikon
 * Author URI:			https://aikon.se
 * License:				MIT
 * Text Domain: 		aikon-role-manager
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// Check minimum PHP version
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function () {
        sprintf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('Aikon Role Manager requires PHP 8.0 or higher.', 'aikon-role-manager')
        );
    });
    exit;
}

define('ARM_VERSION', '1.0.0');
define('ARM_PATH', plugin_dir_path(__FILE__));
define('ARM_URL', plugin_dir_url(__FILE__));
define('ARM_TEMPLATE_PATH', ARM_PATH . 'templates');

require_once __DIR__ . '/vendor/autoload.php';

use Aikon\RoleManager\OptionsPage\OptionsPage;
use Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab;
use Aikon\RoleManager\OptionsPage\Tabs\PostTypesTab;
use Aikon\RoleManager\OptionsPage\Tabs\RolesTab;
use Aikon\RoleManager\UserProfile\UserProfileEdit;

/** Register the Options page and pass the RoleManager with a config */
add_action('admin_menu', function () {
    new OptionsPage([
        new RolesTab(),
        new CapabilitiesTab(),
        new PostTypesTab(),
    ]);
    new UserProfileEdit();
}, 10);

/**
 * When site is multisite the super_admin has the multisite_capabilities,
 * if not multisite there should be no super_admin role and the administrator
 * should have the multisite_capabilities
 */
add_filter('aikon_role_manager_config', function ($config) {
    if (is_multisite()) {
        $config['default_capabilities']['super_admin'] = array_merge(
            $config['default_capabilities']['super_admin'],
            $config['multisite_capabilities']
        );
    } else {
        $config['default_capabilities']['administrator'] = array_merge(
            $config['default_capabilities']['administrator'],
            $config['multisite_capabilities']
        );
    }

    return $config;
}, 10, 1);
