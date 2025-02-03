<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use function Aikon\RoleManager\config;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HandlesActions;
use Aikon\RoleManager\OptionsPage\Traits\HandlesNotice;

use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;

use function Aikon\RoleManager\template;

class CapabilitiesTab implements TabInterface
{
    use HasTitleAnSlug;
    use HandlesActions;
    use HandlesNotice;

    private RoleManager $manager;

    public function __construct()
    {
        $this->title = __('Capabilities', 'aikon-role-manager');
        $this->slug = 'capabilities';
        $this->icon = 'dashicons-privacy';

        $this->manager = RoleManager::getInstance();
    }

    public function handle(): void
    {
        $this->middleware(function ($request): void {
            if (!current_user_can('manage_options')) {
                throw new \Exception('You do not have permission to access this page.');
            }
        });

    }

    /**
      * Categories capabilities
      *
      * @param string[] $capabilities
      * @return array<string, array{label: string, caps: string[]}>
      */
    public function categorized_capabilities(array $capabilities): array
    {
        $sorted_caps = [];

        $capabilities = array_unique($capabilities);
        $capabilities = array_combine($capabilities, $capabilities); // cap => cap

        // Add post types capabilities
        /** @var array<string,bool> */
        $args = config('post_type_query', [
            'show_ui' => true
        ]);
        /** @var \WP_Post_Type[] */
        $post_types = get_post_types($args, 'objects');

        foreach ($post_types as $post_type) {
            $sorted_caps[$post_type->name] = [ 'label' => $post_type->label, 'caps' => []];

            /** @var array<string,string> */
            $caps = (array) $post_type->cap;
            foreach ($caps as $value) {
                $sorted_caps[$post_type->name]['caps'][] = $value;
                if (isset($capabilities[$value])) {
                    unset($capabilities[$value]);
                }
            }
        }

        // Add core capabilities
        /** @var array<string,string[]> */
        $defaul_capabilities = config('default_capabilities', []);
        foreach ($defaul_capabilities as $role => $caps) {
            $label = ucwords(str_replace('_', ' ', $role));
            $sorted_caps[$role] = [ 'label' => $label, 'caps' => []];
            foreach ($caps as $cap) {
                $sorted_caps[$role]['caps'][] = $cap;
                if (isset($capabilities[$cap])) {
                    unset($capabilities[$cap]);
                }
            }
        }

        // Add remaining capabilities
        $sorted_caps['zcustom'] = [ 'label' => 'Custom', 'caps' => array_values($capabilities)];
        ksort($sorted_caps);

        // Filter sorted caps and remove if it has no caps
        $sorted_caps = array_filter($sorted_caps, function ($cat) {
            return count($cat['caps']) > 0;
        });

        return $sorted_caps;
    }

    /**
     * Check role has capability
     *
     * @param string $role
     * @param string $cap
     * @return boolean
     */
    public function role_has_cap(string $role, string $cap): bool
    {
        return $this->manager->role_has_cap($role, $cap);
    }

    public function render(): void
    {
        $roles = $this->manager->current_roles();
        $slugs = array_keys($roles);
        $default_selected = $slugs[0];

        $nav = array_combine(
            $slugs,
            array_map(fn ($role) => $role['name'], $roles)
        );

        $current = (isset($_GET['role']) && is_string($_GET['role']))
            ? $this->manager->validate_role_slug($_GET['role'])
            : $default_selected;

        if (!in_array($current, $slugs)) {
            $current = $default_selected;
        }

        template('tab-capabilities', [
            'view' => $this,
            'nav' => $nav,
            'current' => $current,
            'role' => $roles[$current],
            'all_capabilities' => $this->manager->all_capabilities(),
        ]);
    }
}
