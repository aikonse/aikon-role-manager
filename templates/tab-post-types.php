<?php
/**
 * @var array<string, array{label: string, capabilities: array<string,string>}> $post_types
 */
?>
<div class="arm_capabilities-card-view">
    <?php foreach ($post_types as $type => $data): ?>
        <div class="arm_capabilities-card">
            <h2><?php echo esc_html($data['label']); ?></h2>
            <ul>
                <?php foreach ($data['capabilities'] as $operation => $capability): ?>
                    <li>
                        <strong><?php echo esc_html($operation); ?>:</strong>
                        <?php echo esc_html($capability); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>