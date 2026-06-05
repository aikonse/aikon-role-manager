<?php
/**
 * @var string $post_type
 * @var string $label
 * @var array<string, string> $capabilities
 * @var string|null $override
 * @var bool $has_override
 * @var string $tab
 * @var array<string, string> $errors
 */

if (! defined('ABSPATH')) {
    exit;
}

use function Aikon\RoleManager\url_parser;

$back_url      = url_parser(['tab' => $tab], ['edit_post_type']);
$remove_url    = url_parser(['action' => 'remove_post_type_override', 'post_type' => $post_type]);
$invalid_cap   = $errors['capability_type'] ?? false;

$current_value = $override ?? '';
if (isset($_POST['capability_type'])) {
    $current_value = sanitize_key($_POST['capability_type']);
}
?>
<h2><?php echo esc_html($label); ?> &mdash; <?php esc_html_e('Capability Override', 'aikon-role-manager'); ?></h2>

<form method="post" class="validate">
    <input type="hidden" name="action" value="save_post_type_override">
    <input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">

    <table class="form-table" role="presentation">
        <tbody>
            <tr class="form-field form-required <?php echo $invalid_cap ? 'form-invalid' : ''; ?>">
                <th scope="row">
                    <label for="capability_type"><?php esc_html_e('Capability Type', 'aikon-role-manager'); ?></label>
                </th>
                <td>
                    <input
                        name="capability_type"
                        id="capability_type"
                        type="text"
                        value="<?php echo esc_attr($current_value); ?>"
                        size="40"
                        placeholder="<?php echo esc_attr($post_type); ?>"
                        aria-required="true"
                        aria-describedby="capability_type-description"
                        required
                        minlength="1"
                    >
                    <p class="description" id="capability_type-description">
                        <?php esc_html_e('The primitive type used to generate capabilities (e.g. "post", "page", or a custom slug). Saving clears any explicit capability mapping and lets WordPress regenerate from this type.', 'aikon-role-manager'); ?>
                    </p>
                    <?php if ($invalid_cap): ?>
                        <p class="form-invalid-message"><?php echo esc_html($invalid_cap); ?></p>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>

    <?php if (!empty($capabilities)): ?>
        <h3><?php esc_html_e('Current Capabilities', 'aikon-role-manager'); ?></h3>
        <p class="description">
            <?php esc_html_e('These reflect the active capability mapping after any override has been applied.', 'aikon-role-manager'); ?>
        </p>
        <table class="wp-list-table widefat fixed striped" style="margin-bottom:1.5rem;">
            <thead>
                <tr>
                    <th><?php esc_html_e('Operation', 'aikon-role-manager'); ?></th>
                    <th><?php esc_html_e('Capability', 'aikon-role-manager'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($capabilities as $operation => $capability): ?>
                    <tr>
                        <td><code><?php echo esc_html($operation); ?></code></td>
                        <td><?php echo esc_html($capability); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="edit-tag-actions">
        <input type="submit" class="button button-primary" value="<?php esc_attr_e('Save Override', 'aikon-role-manager'); ?>">
        <?php if ($has_override): ?>
            <a
                href="<?php echo esc_url($remove_url); ?>"
                class="button"
                onclick="return confirm('<?php esc_attr_e('Remove the override and restore default capabilities?', 'aikon-role-manager'); ?>')"
            ><?php esc_html_e('Remove Override', 'aikon-role-manager'); ?></a>
        <?php endif; ?>
    </div>
    <div class="edit-tag-actions">
        <a href="<?php echo esc_url($back_url); ?>">&larr; <?php esc_html_e('Back', 'aikon-role-manager'); ?></a>
    </div>
</form>
