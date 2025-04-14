<?php
/**
 * @var string $page
 * @var string $current_tab
 * @var array<string, array<string, string>> $tabs
 * @var string $title
 * @var string $icon
 */
?>
<h2 class="nav-tab-wrapper">
    <?php
    foreach ($tabs as $tab) :
        [
            'title' => $title,
            'slug' => $slug,
            'icon' => $icon
        ] = $tab;
    ?>
        <a
            href="?page=<?php echo esc_attr($page); ?>&tab=<?php echo esc_attr($slug); ?>"
            class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
            <?php echo esc_html($title); ?>
        </a>
    <?php endforeach; ?>
</h2>