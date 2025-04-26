<?php
/**
 * @var string $title
 * @var string $tagline
 * @var string $current_tab
 * @var Aikon\RoleManager\Manager\Page $page
 * @var Aikon\RoleManager\Manager\Tab $tab
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html($title); ?></h1>
    <p><?php echo esc_html($tagline); ?></p>
    <?php $page->render_tab_nav($current_tab); ?>
    <div class="tab-content">
        <?php $tab->render(); ?>
    </div>
</div>