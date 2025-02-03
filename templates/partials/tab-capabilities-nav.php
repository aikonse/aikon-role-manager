<?php
use function Aikon\RoleManager\url_parser;
?>
<ul class="subsubsub">
    <?php foreach ( $nav as $slug => $name ): ?>
        <li>
            <a href="<?php echo url_parser(['show_role' => $slug]); ?>" class="<?php echo $current === $slug ? 'current' : ''; ?>">
                <?php echo $name; ?>
            </a>
        </li>
        |
    <?php endforeach; ?>
</ul>
<div class="clear"></div>