<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Traits;

trait HandlesNotice
{
    /**
     * Add a notice
     *
     * @param string $message The message to show
     * @param string $type The type of notice
     * @return void
     */
    public function add_notice(string $message, string $type = 'success'): void
    {
        $type = in_array($type, [
            'info',
            'error',
            'success',
            'warning'
        ]) ? $type : 'success';

        add_action('admin_notices', function () use ($message, $type): void {
            printf('<div class="notice notice-%s"><p>%s</p></div>', esc_attr($type), esc_html($message));
        });
    }
}
