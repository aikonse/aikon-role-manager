<?php

/**
 * @var bool $is_current_user_role
 */

if (! defined('ABSPATH')) {
    exit;
}

if (!$is_current_user_role) {
    return;
}
?>
<div id="capabilities_warning" class="notice notice-warning is-dismissible">
    <p><?php esc_html_e('Warning: You are editing the capabilities of a role you have. Be careful when changing these capabilities. You may lock yourself out.', 'aikon-role-manager'); ?></p>
</div>