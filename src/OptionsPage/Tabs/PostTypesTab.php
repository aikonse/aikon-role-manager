<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use function Aikon\RoleManager\config;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;

use function Aikon\RoleManager\template;

class PostTypesTab implements TabInterface
{
    use HasTitleAnSlug;

    public function __construct()
    {
        $this->title = __('Post Types Capabilities', 'aikon-role-manager');
        $this->slug = 'post-types';
        $this->icon = 'dashicons-admin-post';
    }

    public function handle(): void
    {
        // No form handling needed for this view-only tab
    }

    /**
     * Get all post types with their capabilities
     * @return array<string, array{label: string, capabilities: array<string,string>}>
     */
    private function get_post_types_capabilities(): array
    {
        /** @var array<string,bool> */
        $args = config('post_type_query', [
            'show_ui' => true
        ]);

        /** @var \WP_Post_Type[] */
        $post_types = get_post_types($args, 'objects');
        $result = [];

        foreach ($post_types as $post_type) {
            /** @var array<string,string> */
            $capabilities = (array) $post_type->cap;
            $result[$post_type->name] = [
                'label' => $post_type->label,
                'capabilities' => $capabilities,
            ];
        }

        return $result;
    }

    public function render(): void
    {
        template('tab-post-types', [
            'post_types' => $this->get_post_types_capabilities(),
        ]);
    }
}
