<?php
/**
 * @var array<string, string> $errors
 */

if (! defined('ABSPATH')) {
    exit;
}

$role_name_value = isset($_POST['name']) ? (string) $_POST['name'] : '';
$role_slug_value = isset($_POST['slug']) ? (string) $_POST['slug'] : '';

$role_name_invalid = $errors['name'] ?? false;
$role_slug_invalid = $errors['slug'] ?? false;

?>
<div id="col-left" class="col-wrap">
    <div class="form-wrap">
        <h2><?php _e('Add new role', 'aikon-role-manager'); ?></h2>
        <form id="addrole" method="post" action="" class="validate">
            <input type="hidden" name="action" value="add_role">
            <div class="form-field form-required term-name-wrap <?php echo $role_name_invalid ? 'form-invalid' : ''; ?>">
                <label for="role-name"><?php _e('Role name', 'aikon-role-manager'); ?></label>
                <input name="name" id="role_name" type="text" value="<?php echo esc_attr($role_name_value); ?>" size="40" aria-required="true" aria-describedby="name-description">
                <p id="name-description"><?php _e('The new role display name', 'aikon-role-manager'); ?></p>
            </div>
            <div class="form-field form-required term-slug-wrap <?php echo $role_slug_invalid ? 'form-invalid' : ''; ?>">
                <label for="role-slug"><?php _e('Slug', 'aikon-role-manager'); ?></label>
                <input name="slug" id="role-slug" type="text" value="<?php echo esc_attr($role_slug_value); ?>" size="40" aria-describedby="slug-description">
                <p id="slug-description"><?php _e('A slug is the url friendly version of the name. It has lowercase a-z letters and underscores.', 'aikon-role-manager'); ?></p>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add new role', 'aikon-role-manager'); ?>">
            </p>
        </form>
    </div>
</div>