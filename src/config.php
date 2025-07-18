<?php

declare(strict_types=1);

namespace Aikon\RoleManager;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Plugin configuration
 *
 * @var array<string, array<string>|mixed>
 */
return [
    // Query to get show post types and their capabilities
    'post_type_query' => [
        'show_ui' => true,
    ],
    // Capabilities that are reqired to manage roles and capabilities
    'can' => [
        'update_user_roles'     => 'promote_users',
        'edit_roles'            => 'manage_options',
        'delete_roles'          => 'manage_options',
        'create_roles'          => 'manage_options',
        'edit_role_capabilities' => 'manage_options',
    ],

    // Default capabilities for each role
    'default_capabilities' => [
        'super_admin' => [
            'create_sites',
            'delete_sites',
            'manage_network',
            'manage_sites',
            'manage_network_users',
            'manage_network_plugins',
            'manage_network_themes',
            'manage_network_options',
            'upgrade_network',
            'setup_network',
        ],
        'administrator' => [
            'activate_plugins',
            'create_users',
            'delete_others_pages',
            'delete_others_posts',
            'delete_pages',
            'delete_posts',
            'delete_private_pages',
            'delete_private_posts',
            'delete_published_pages',
            'delete_published_posts',
            'edit_dashboard',
            'edit_users',
            'edit_others_pages',
            'edit_others_posts',
            'edit_pages',
            'edit_posts',
            'edit_private_pages',
            'edit_private_posts',
            'edit_published_pages',
            'edit_published_posts',
            'edit_plugins',
            'edit_files',
            'edit_themes',
            'edit_theme_options',
            'export',
            'import',
            'list_users',
            'manage_categories',
            'manage_links',
            'manage_options',
            'moderate_comments',
            'promote_users',
            'delete_users',
            'publish_pages',
            'publish_posts',
            'read_private_pages',
            'read_private_posts',
            'read',
            'remove_users',
            'switch_themes',
            'upload_files',
            'customize',
            'delete_site',
        ],
        'editor' => [
            'delete_others_pages',
            'delete_others_posts',
            'delete_pages',
            'delete_posts',
            'delete_private_pages',
            'delete_private_posts',
            'delete_published_pages',
            'delete_published_posts',
            'edit_others_pages',
            'edit_others_posts',
            'edit_pages',
            'edit_posts',
            'edit_private_pages',
            'edit_private_posts',
            'edit_published_pages',
            'edit_published_posts',
            'publish_pages',
            'publish_posts',
            'read',
            'upload_files',
        ],
        'author' => [
            'create_posts',
            'delete_posts',
            'delete_published_posts',
            'edit_posts',
            'edit_published_posts',
            'publish_posts',
            'read',
            'upload_files',
        ],
        'contributor' => [
            'delete_posts',
            'edit_posts',
            'read',
        ],
        'subscriber' => [
            'read',
        ],
    ],
    'multisite_capabilities' => [
        'update_core',
        'update_plugins',
        'update_themes',
        'install_plugins',
        'install_themes',
        'delete_themes',
        'delete_plugins',
        'edit_plugins',
        'edit_themes',
        'edit_files',
        'edit_users',
        'add_users',
        'create_users',
        'delete_users',
        'unfiltered_html',
    ]
];
