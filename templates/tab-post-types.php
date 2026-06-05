<?php
/**
 * @var array<string, array{label: string, capabilities: array<string,string>}> $post_types
 * @var array<string, string> $overrides
 * @var string $tab
 */

if (! defined('ABSPATH')) {
    exit;
}

use function Aikon\RoleManager\url_parser;

?>
<div style="padding-block: 2rem;">
<table class="wp-list-table widefat fixed striped posts">
    <thead>
        <tr>
            <th scope="col" class="manage-column column-title column-primary">
                <span><?php esc_html_e('Post Type', 'aikon-role-manager'); ?></span>
            </th>
            <th scope="col" class="manage-column">
                <?php esc_html_e('Capability Type', 'aikon-role-manager'); ?>
            </th>
        </tr>
    </thead>

    <tbody id="the-list">
        <?php foreach ($post_types as $type => $data):
            $edit_url   = url_parser(['tab' => $tab, 'edit_post_type_capability' => $type]);
            $has_override = isset($overrides[$type]);
            ?>
            <tr>
                <td class="title column-title has-row-actions column-primary" data-colname="Post Type">
                    <strong>
                        <a href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($data['label']); ?></a>
                    </strong>
                    <div class="row-actions">
                        <span class="edit">
                            <a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit override', 'aikon-role-manager'); ?></a>
                        </span>
                    </div>
                </td>
                <td data-colname="Capability Type">
                    <?php if ($has_override): ?>
                        <code><?php echo esc_html($overrides[$type]); ?></code>
                        <span class="dashicons dashicons-edit" title="<?php esc_attr_e('Override active', 'aikon-role-manager'); ?>" style="color:#2271b1;vertical-align:middle;"></span>
                    <?php else: ?>
                        <span class="description"><?php esc_html_e('(default)', 'aikon-role-manager'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

    <tfoot>
        <tr>
            <th scope="col" class="manage-column column-title column-primary">
                <span><?php esc_html_e('Post Type', 'aikon-role-manager'); ?></span>
            </th>
            <th scope="col" class="manage-column">
                <?php esc_html_e('Capability Type', 'aikon-role-manager'); ?>
            </th>
        </tr>
    </tfoot>
</table>
</div>
