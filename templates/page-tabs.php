
<h2 class="nav-tab-wrapper">
    <?php foreach ($tabs as $tab => $title) : ?>
        <a 
            href="?page=<?php echo $page_slug;?>&tab=<?php echo $tab; ?>" 
            class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>"
        >
            <?php echo $title; ?>
        </a>
    <?php endforeach; ?>
</h2>