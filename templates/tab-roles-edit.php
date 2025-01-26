<?php

use function Aikon\RoleManager\url_parser;

$delete_url = url_parser(['action' => 'delete_role', 'delete_role' => $slug]);
$view_url = url_parser(['tab' => 'edit_roles', 'edit_role' => false]);

$invalid_name = $errors['name'] ?? false;
$invalid_slug = $errors['slug'] ?? false;
?>
<h2><?php _e('Edit role'); ?></h2>
<form method="post" class="validate" id="edittag">
    <input type="hidden" name="action" value="update_role">
    <input type="hidden" name="role" value="<?php echo $slug; ?>">

    <table class="form-table" role="presentation">
        <tbody>
            <tr
                class="form-field form-required term-name-wrap <?php echo $invalid_name ? 'form-invalid' : ''; ?>">
                <th scope="row"><label for="name"><?php _e('Name'); ?></label></th>
                <td>
                    <input name="name" id="name" type="text" value="<?php echo $_POST['name'] ?? $name; ?>" size="40" aria-required="true" aria-describedby="name-description">
                    <p class="description" id="name-description"><?php _e('The role name'); ?></p>
                </td>
            </tr>
            <tr
                class="form-field form-required term-slug-wrap <?php echo $invalid_slug ? 'form-invalid' : ''; ?>">
                <th scope="row"><label for="slug"><?php _e('Slug'); ?></label></th>
                <td>
                    <input name="slug" id="slug" type="text" value="<?php echo $_POST['slug'] ?? $slug; ?>" size="40" aria-describedby="slug-description" <?php echo $is_default ? 'disabled' : ''; ?>>
                    <p class="description" id="slug-description"><?php _e('A slug is the url friendly version of the name. It has lowercase a-z letters and underscores.', 'aikon-role-manager'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="edit-tag-actions">

        <input type="submit" class="button button-primary" value="<?php _e('Update'); ?>">
        <?php if (!$is_default): ?>
            <span id="delete-link">
                <a href="#0" data-url="<?php echo $delete_url; ?>" data-role="<?php echo $role['slug']; ?>" class="delete delete_role_button"><?php _e('Delete'); ?></a>
            </span>
        <?php endif; ?>
    </div>
    <div class="edit-tag-actions">
        <a class="link" href="<?php echo $view_url; ?>" class="button">&larr; <?php _e('Back'); ?></a>
    </div>
</form>