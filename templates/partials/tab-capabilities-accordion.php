<?php
/**
 * @var string $current
 * @var Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab $view
 * @var array<string, string> $all_capabilities
 * @var string[] $restricted_capabilities
 * @var bool $is_role_protected
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div id="add-capabilities-list" class="arm-postbox">
    <div class="arm-postbox-header">
        <h2><?php esc_html_e('Capabilities', 'aikon-role-manager');?></h2>
    </div>
    <div class="arm-postbox-form">
        <label for="capability-filter"><?php esc_html_e('Add new capability', 'aikon-role-manager');?></label>
        <input type="text" pattern="[a-z_0-9]+" required minlength="2" maxlength="20" id="new-capability" name="filter_roles" value="" placeholder="" <?php disabled($is_role_protected); ?>>
        <span class="description"><?php esc_html_e('Use lowercase letters, underscores and numbers', 'aikon-role-manager');?></span>
        <button type="button" id="add-capability" class="button button-primary" <?php disabled($is_role_protected); ?>><?php esc_html_e('Add', 'aikon-role-manager');?></button>
    </div>
    <?php foreach ($view->categorized_capabilities($all_capabilities) as $cat_slug => $cat): ?>
    <div class="arm-postbox-accordion" aria-expanded="false">
        <div class="arm-postbox-accordion-header">
            <h2><?php echo esc_html($cat['label']); ?></h2>
            <button type="button" class="handlediv" aria-expanded="true">
                <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
        </div>
        <div class="arm-postbox-accordion-content">
            <ul>
            <?php foreach ($cat['caps'] as $cap): ?>
                <li>
                    <label>
                        <input type="checkbox" name="role_caps[<?php echo esc_attr($cap); ?>]" <?php echo $is_role_protected || $view->role_has_cap($current, $cap) || in_array($cap, $restricted_capabilities, true) ? 'disabled' : '';?> value="1">
                        <?php echo esc_html($cap); ?>
                    </label>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="arm-postbox-footer">
        <input type="submit" id="add-capabilities" disabled class="button button-primary button-large" value="<?php esc_html_e('Add capabilities', 'aikon-role-manager');?>">
    </div>
</div>