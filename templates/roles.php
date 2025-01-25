<?php

use function Aikon\RoleManager\url_parser;

$roles = array_map(function ($slug, $role) {
    return [
        'slug' => $slug,
        'name' => $role['name'],
    ];
}, array_keys($roles), $roles);

$role_name_value = $_POST['name'] ?? '';
$role_slug_value = $_POST['slug'] ?? '';

$role_name_invalid = false; //$view->action_errors('name');
$role_slug_invalid = false ///$view->action_errors('slug');
?>
<div id="col-container" style="padding-block: 2rem;" class="wp-clearfix">
    <div id="col-left" class="col-wrap">
        <div class="form-wrap">
            <h2><?php _e('Add new role', 'aikon-role-manager');?></h2>
            <form id="addtag" method="post" action="" class="validate">
                <input type="hidden" name="action" value="create_role">
                <div class="form-field form-required term-name-wrap <?php echo $role_name_invalid ? 'form-invalid' : ''; ?>">
                    <label for="role-name"><?php _e('Role name', 'aikon-role-manager');?></label>
                    <input name="name" id="role_name" type="text" value="<?php echo $role_name_value;?>" size="40" aria-required="true" aria-describedby="name-description">
                    <p id="name-description"><?php _e('The new role display name', 'aikon-role-manager');?></p>
                </div>
                <div class="form-field form-required term-slug-wrap <?php echo $role_slug_invalid ? 'form-invalid' : ''; ?>">
                    <label for="role-slug"><?php _e('Slug');?></label>
                    <input name="slug" id="role-slug" type="text" value="<?php echo $role_slug_value;?>" size="40" aria-describedby="slug-description">
                    <p id="slug-description"><?php _e('A slug is the url friendly version of the name. It has lowercase a-z letters and underscores.', 'aikon-role-manager');?></p>
                </div>

                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Add new role', 'aikon-role-manager');?>">
                </p>
            </form>
        </div>
    </div>

    <div id="col-right" class="col-wrap">

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary">
                        <span><?php _e('Role');?></span>
                    </th>
                    <th scope="col" id="slug" class="manage-column column-slug"><?php _e('Slug');?></th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php
                foreach($roles as $role):
                    $delete_url = url_parser([
                        'action' => 'delete_role',
                        'delete_role' => $role['slug'],
                    ]);

                    $edit_url = url_parser([
                        'edit_role' => $role['slug'],
                    ]);

                    $edit_caps_url = url_parser([
                        'tab' => 'handle_role_caps',
                        'show_role' => $role['slug'],
                    ]);
                    ?>
                <tr class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry">
                    <td class="title column-title has-row-actions column-primary page-title" data-colname="Role">

                        <strong>
                            <a href="<?php echo $edit_caps_url;?>"><?php echo $role['name']; ?></a>
                        </strong>

                        <div class="row-actions">
                            <span class="edit"><a href="<?php echo $edit_url;?>"><?php _e('Edit');?></a> | </span>
                            <?php if (!$manager->is_default_role($role['slug'])): ?>
                            <span class="trash"><a href="#0" data-url="<?php echo $delete_url;?>" data-role="<?php echo $role['slug'];?>" class="submitdelete delete_role_button"><?php _e('Delete');?></a> | </span>
                            <?php endif; ?>
                            <span class="view"><a href="<?php echo $edit_caps_url;?>"><?php _e('Show/edit capabilities', 'aikon-role-manager');?></a></span>
                        </div>
                    </td>
                    <td class="slug column-slug" data-colname="Slug">
                        <?php echo $role['slug']; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary">
                        <span><?php _e('Role');?></span>
                    </th>
                    <th scope="col" class="manage-column column-slug"><?php _e('Slug');?></th>
                </tr>
            </tfoot>
        </table>
    </div>