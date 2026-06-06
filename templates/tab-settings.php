<?php
/**
 * @var array<string, array{name: string, capabilities: array<string,bool>}> $roles
 * @var \WP_Post_Type[] $post_types
 * @var string[] $protected_roles
 * @var string[] $protected_post_types
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<form method="post" style="padding-block: 2rem;">
    <input type="hidden" name="action" value="save_settings">

    <h2><?php esc_html_e('Protected Roles', 'aikon-role-manager'); ?></h2>
    <p class="description">
        <?php esc_html_e('Protected roles cannot be edited, renamed, deleted, or have their capabilities changed.', 'aikon-role-manager'); ?>
    </p>

    <table class="wp-list-table widefat fixed striped posts" style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th scope="col" class="manage-column check-column"><span class="screen-reader-text"><?php esc_html_e('Protected', 'aikon-role-manager'); ?></span></th>
                <th scope="col" class="manage-column column-title column-primary">
                    <?php esc_html_e('Role', 'aikon-role-manager'); ?>
                </th>
                <th scope="col" class="manage-column">
                    <?php esc_html_e('Slug', 'aikon-role-manager'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($roles as $slug => $role): ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input
                            type="checkbox"
                            name="protected_roles[]"
                            value="<?php echo esc_attr($slug); ?>"
                            <?php checked(in_array($slug, $protected_roles, true)); ?>
                        >
                    </th>
                    <td class="column-title column-primary">
                        <strong><?php echo esc_html($role['name']); ?></strong>
                    </td>
                    <td>
                        <code><?php echo esc_html($slug); ?></code>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2><?php esc_html_e('Protected Post Types', 'aikon-role-manager'); ?></h2>
    <p class="description">
        <?php esc_html_e('Protected post types cannot have their capability type override added, changed, or removed.', 'aikon-role-manager'); ?>
    </p>

    <table class="wp-list-table widefat fixed striped posts" style="margin-bottom: 2rem;">
        <thead>
            <tr>
                <th scope="col" class="manage-column check-column"><span class="screen-reader-text"><?php esc_html_e('Protected', 'aikon-role-manager'); ?></span></th>
                <th scope="col" class="manage-column column-title column-primary">
                    <?php esc_html_e('Post Type', 'aikon-role-manager'); ?>
                </th>
                <th scope="col" class="manage-column">
                    <?php esc_html_e('Slug', 'aikon-role-manager'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($post_types as $slug => $post_type): ?>
                <tr>
                    <th scope="row" class="check-column">
                        <input
                            type="checkbox"
                            name="protected_post_types[]"
                            value="<?php echo esc_attr($slug); ?>"
                            <?php checked(in_array($slug, $protected_post_types, true)); ?>
                        >
                    </th>
                    <td class="column-title column-primary">
                        <strong><?php echo esc_html($post_type->label); ?></strong>
                    </td>
                    <td>
                        <code><?php echo esc_html($slug); ?></code>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'aikon-role-manager'); ?>">
</form>
