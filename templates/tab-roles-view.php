<?php
/**
 * @var string $tab
 * @var array<string, array{name: string, capabilities: array<string,bool>}> $roles
 * @var Aikon\RoleManager\Manager\RoleManager $manager
 * @var Aikon\RoleManager\OptionsPage\Tabs\RolesTab $view
 * @var array<string, string> $errors
 */

if (! defined('ABSPATH')) {
    exit;
}

use function Aikon\RoleManager\template;
use function Aikon\RoleManager\url_parser;

/**
 * @param string $slug
 * @param array{name: string, capabilities: array<string,bool>} $role
 * @return array<int, array{slug: string, name: string}>
 */
$roles = array_map(function ($slug, $role) {
    return [
        'slug' => $slug,
        'name' => $role['name'],
    ];
}, array_keys($roles), $roles);

?>
<div id="col-container" style="padding-block: 2rem;" class="wp-clearfix">
    <?php
    template('partials/tab-roles-add-form', [
        'view' => $view,
        'errors' => $errors,
    ]);
?>

    <div id="col-right" class="col-wrap">

        <table class="wp-list-table widefat fixed striped posts">
            <thead>
                <tr>
                    <th scope="col" id="title" class="manage-column column-title column-primary">
                        <span><?php esc_html_e('Role', 'aikon-role-manager'); ?></span>
                    </th>
                    <th scope="col" id="slug" class="manage-column column-slug"><?php esc_html_e('Slug', 'aikon-role-manager'); ?></th>
                </tr>
            </thead>

            <tbody id="the-list">
                <?php
            foreach ($roles as $role):
                $delete_url = url_parser([
                    'tab' => $tab,
                    'action' => 'delete_role',
                    'delete_role' => $role['slug'],
                ]);

                $edit_url = url_parser([
                    'tab' => $tab,
                    'edit_role' => $role['slug'],
                ]);

                $edit_caps_url = url_parser([
                    'tab' => 'capabilities',
                    'role' => $role['slug'],
                ]);
                ?>
                    <tr class="iedit author-self level-0 post-1 type-post status-publish format-standard hentry">
                        <td class="title column-title has-row-actions column-primary page-title" data-colname="Role">

                            <strong>
                                <a href="<?php echo esc_attr($edit_caps_url); ?>"><?php echo esc_html($role['name']); ?></a>
                            </strong>

                            <div class="row-actions">
                                <span class="edit"><a href="<?php esc_attr_e($edit_url); ?>"><?php esc_html_e('Edit', 'aikon-role-manager'); ?></a> | </span>
                                <?php if (!$manager->is_default_role($role['slug'])): ?>
                                    <span class="trash">
                                        <a 
                                            href="#0" 
                                            data-action="<?php echo esc_attr($delete_url); ?>" 
                                            data-confirmationmessage="<?php esc_html_e('Are you sure you want to delete this role?', 'aikon-role-manager'); ?>" 
                                            class="submitdelete delete_role_button"
                                        ><?php esc_html_e('Delete', 'aikon-role-manager'); ?></a> | 
                                    </span>
                                <?php endif; ?>
                                <span class="view">
                                    <a 
                                        href="<?php echo esc_attr($edit_caps_url); ?>"
                                    ><?php esc_html_e('Show/edit capabilities', 'aikon-role-manager'); ?></a>
                                </span>
                            </div>
                        </td>
                        <td class="slug column-slug" data-colname="Slug">
                            <?php echo esc_html($role['slug']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-title column-primary">
                        <span><?php esc_html_e('Role', 'aikon-role-manager'); ?></span>
                    </th>
                    <th scope="col" class="manage-column column-slug"><?php esc_html_e('Slug', 'aikon-role-manager'); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>