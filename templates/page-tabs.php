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
            href="?page=<?php echo $page; ?>&tab=<?php echo $slug; ?>"
            class="nav-tab <?php echo $current_tab === $slug ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons <?php echo $icon; ?>"></span>
            <?php echo $title; ?>
        </a>
    <?php endforeach; ?>
</h2>