<?php
/**
 * @var array<string, array<string, array{name: string, capabilities: array<string>}>> $user
 * @var array<string, array<string, array{name: string, capabilities: array<string>}>> $roles
 * @var string $primary_role
 * @var array<string, mixed> $user_other_roles
 * @var string $form
 * @var boolean $can_edit_roles
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>
<h3><?php _e('Additional Roles', 'aikon-role-manager'); ?></h3>
<p><?php _e('Select additional roles for this user.', 'aikon-role-manager'); ?></p>

<table class="form-table">
    <tr>
        <th>
            <label for="additional_roles"><?php _e('Additional Roles', 'aikon-role-manager'); ?></label>
        </th>
        <td>
            <ul>
                <?php foreach($roles as $role => $data): if($role == $primary_role) continue; ?>
                    <li>
                        <label>
                            <input 
                                type="checkbox" 
                                <?php if(!$can_edit_roles) echo 'disabled'; ?>
                                name="<?php echo esc_attr( $form);?>[]" 
                                value="<?php echo esc_attr($role); ?>" <?php echo in_array($role, $user_other_roles) ? 'checked' : ''; ?>
                            >
                            <?php echo esc_html($data['name']); ?>
                        </label>
                    </li>
                <?php endforeach; ?>
            </ul>
        </td>
    </tr>
</table>