<?php
/**
 * @var string $slug
 * @var string $name
 * @var bool $is_default
 * @var array<string, string> $errors
 */

if (! defined('ABSPATH')) {
    exit;
}

use function Aikon\RoleManager\url_parser;

$delete_url = url_parser(['action' => 'delete_role', 'delete_role' => $slug]);
$view_url = url_parser(['tab' => 'edit_roles', 'edit_role' => false]);

$invalid_name = $errors['name'] ?? false;
$invalid_slug = $errors['slug'] ?? false;

if (isset($_POST['name'])) {
    $name = sanitize_text_field($_POST['name']);
}

if (isset($_POST['slug'])) {
    $slug = sanitize_text_field($_POST['slug']);
}

?>
<h2><?php esc_html_e('Edit role', 'aikon-role-manager'); ?></h2>
<form method="post" class="validate" id="edittag">
    <input type="hidden" name="action" value="update_role">
    <input type="hidden" name="role" value="<?php echo esc_attr($slug); ?>">

    <table class="form-table" role="presentation">
        <tbody>
            <tr class="form-field form-required term-name-wrap <?php echo $invalid_name ? 'form-invalid' : ''; ?>">
                <th scope="row"><label for="name"><?php esc_html_e('Name', 'aikon-role-manager'); ?></label></th>
                <td>
                    <input name="name" id="name" type="text" value="<?php echo esc_attr($name); ?>" size="40" aria-required="true" aria-describedby="name-description">
                    <p class="description" id="name-description"><?php esc_html_e('The role name', 'aikon-role-manager'); ?></p>
                </td>
            </tr>
            <tr class="form-field form-required term-slug-wrap <?php echo $invalid_slug ? 'form-invalid' : ''; ?>">
                <th scope="row"><label for="slug"><?php esc_html_e('Slug', 'aikon-role-manager'); ?></label></th>
                <td>
                    <input name="slug" id="slug" type="text" value="<?php echo esc_attr($slug); ?>" size="40" aria-describedby="slug-description" <?php echo $is_default ? 'disabled' : ''; ?>>
                    <p class="description" id="slug-description"><?php esc_html_e('A slug is the url friendly version of the name. It has lowercase a-z letters and underscores.', 'aikon-role-manager'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="edit-tag-actions">

        <input type="submit" class="button button-primary" value="<?php esc_html_e('Update', 'aikon-role-manager'); ?>">
        <?php if (!$is_default): ?>
            <span id="delete-link">
                <a 
                    href="#0" 
                    data-url="<?php echo esc_attr($delete_url); ?>" 
                    data-role="<?php echo esc_attr($slug); ?>" 
                    class="delete delete_role_button"
                ><?php esc_html_e('Delete', 'aikon-role-manager'); ?></a>
            </span>
        <?php endif; ?>
    </div>
    <div class="edit-tag-actions">
        <a class="link" href="<?php echo esc_attr($view_url); ?>" class="button">&larr; <?php esc_html_e('Back', 'aikon-role-manager'); ?></a>
    </div>
</form>