<?php
/**
 * @var Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab $view
 * @var array<string, string> $nav
 * @var string $current
 * @var array{name: string, capabilities: array<string,bool>} $role
 * @var array<string, string> $all_capabilities
 * @var Aikon\RoleManager\Manager\RoleManager $manager
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use function Aikon\RoleManager\template;

?>
<?php
    template('partials/tab-capabilities-nav', [
        'nav' => $nav,
        'current' => $current,
    ]);
?>
<p class="search-box">
    <label class=label" for="capability-filter"><?php _e('Filter capabilities','aikon-role-manager');?></label>
    <input type="search" id="capability-filter" name="filter_roles value="">
</p>
<div id="nav-menus-frame" class="wp-clearfix metabox-sortables ui-sortable">
    <div id="menu-settings-column" class="metabox-holder">
        <div class="clear"></div>
        <?php
            /**
             * Render the accordion for the capabilities
             */
            template('partials/tab-capabilities-accordion',[
                'view'    => $view,
                'current' => $current,
                'all_capabilities' => $all_capabilities,
            ]);
        ?>
    </div>

    <div id="menu-management-liquid">
        <div id="menu-management">
            <form action="" method="post" class="arm_roles-manager-form">
                <h2><?php echo esc_attr($role['name']); ?></h2>
                <input type="hidden" name="action" value="save_capabilities">
                <input type="hidden" name="role" value="<?php echo esc_attr($current); ?>">

                <ul 
                    id="capability-list"
                    data-restore-text="<?php _e('Restore','aikon-role-manager'); ?>"
                    data-remove-text="<?php _e('Remove','aikon-role-manager'); ?>"
                >
                    <?php
                        foreach ($role['capabilities'] as $cap => $has_cap) :
                            $input_name = 'role_caps[' . $cap . ']';
                            $is_default = $manager->is_default_default_capabilitiy_for_role($current, $cap);
                    ?>
                    <li class="capability-item <?php echo $is_default ? 'default' : ''; ?>">
                        <input type="hidden" name="<?php echo esc_attr($input_name); ?>" value="0">
                        <label>
                            <input type="checkbox" value="1" name="<?php echo esc_attr($input_name); ?>" <?php echo $has_cap ? 'checked' : ''; ?>>
                            <?php echo esc_html($cap); ?>
                        </label>
                        <?php if ($is_default) : ?>
                                <small class="default-indicator">(default)</small>
                        <?php else: ?>
                        <button
                            type="button"
                            class="button button-small button-text-danger dashicons-before dashicons-trash"
                        ><?php _e('Remove','aikon-role-manager'); ?></button>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="gk-roles-manager-form-toolbar">
                    <button type="submit" class="button button-primary"><?php _e('Save', 'aikon-role-manager');?></button>
                </div>
            </form>
        </div>
    </div>
</div>