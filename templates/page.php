<?php
/**
 * @var string $title
 * @var string $tagline
 * @var string $current_tab
 * @var Aikon\RoleManager\Manager\Page $page
 * @var Aikon\RoleManager\Manager\Tab $tab
 */
?>
<div class="wrap">
    <h1><?php echo $title; ?></h1>
    <p><?php echo $tagline; ?></p>
    <?php $page->render_tab_nav($current_tab); ?>
    <div class="tab-content">
        <?php $tab->render(); ?>
    </div>
</div>