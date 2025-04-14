<?php
/**
 * @var string $current
 * @var Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab $view
 * @var array<string, string> $all_capabilities
 */
?>
<div id="add-capabilities-list" class="arm-postbox">
    <div class="arm-postbox-header">
        <h2><?php _e('Capabilities');?></h2>
    </div>
    <div class="arm-postbox-form">
        <label for="capability-filter"><?php _e('Add new capability','aikon-role-manager');?></label>
        <input type="text" pattern="[a-z_0-9]+" required minlength="2" maxlength="20" id="new-capability" name="filter_roles" value="" placeholder="">
        <span class="description"><?php _e('Use lowercase letters, underscores and numbers','aikon-role-manager');?></span>
        <button type="button" id="add-capability" class="button button-primary"><?php _e('Add');?></button>
    </div>
    <?php foreach($view->categorized_capabilities( $all_capabilities ) as $cat_slug => $cat): ?>
    <div class="arm-postbox-accordion" aria-expanded="false">
        <div class="arm-postbox-accordion-header">
            <h2><?php echo $cat['label']; ?></h2>
            <button type="button" class="handlediv" aria-expanded="true">
                <span class="toggle-indicator" aria-hidden="true"></span>
            </button>
        </div>
        <div class="arm-postbox-accordion-content">
            <ul>
            <?php foreach($cat['caps'] as $cap): ?>
                <li>
                    <label>
                        <input type="checkbox" name="role_caps[<?php echo $cap; ?>]" <?php echo $view->role_has_cap($current, $cap) ? 'disabled':'';?> value="1">
                        <?php echo $cap; ?>
                    </label>
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endforeach; ?>
    <div class="arm-postbox-footer">
        <input type="submit" id="add-capabilities" disabled class="button button-primary button-large" value="<?php _e('Add capabilities','aikon-role-manager');?>">
    </div>
</div>