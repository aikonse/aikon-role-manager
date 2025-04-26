<?php
/**
 * @var array<string, string> $nav
 * @var string $current
 */

if (! defined('ABSPATH')) {
    exit;
}

use function Aikon\RoleManager\url_parser;

?>
<ul class="subsubsub">
    <?php foreach ($nav as $slug => $name): ?>
        <li>
            <a href="<?php echo esc_attr(url_parser(['role' => $slug])); ?>" class="<?php echo $current === $slug ? 'current' : ''; ?>">
                <?php echo esc_html($name); ?>
            </a>
        </li>
        |
    <?php endforeach; ?>
</ul>
<div class="clear"></div>