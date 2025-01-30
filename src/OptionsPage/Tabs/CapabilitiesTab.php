<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;

use function Aikon\RoleManager\template;

class CapabilitiesTab implements TabInterface
{
    use HasTitleAnSlug;

    private RoleManager $manager;

    public function __construct()
    {
        $this->title = 'Capabilities';
        $this->slug = 'capabilities';
        $this->icon = 'dashicons-privacy';

        $this->manager = RoleManager::getInstance();
    }

    public function handle(): void
    {

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

        $queried_role = is_string($_GET['role']) ? $_GET['role'] : '';
        $queried_role = in_array($queried_role, $slugs)
            ? $queried_role
            : $default_selected;

        template('tab-capabilities', [
            'manager' => $this->manager,
            'nav' => $nav,
            'slug' => $queried_role,
            'role' => $roles[$queried_role],
        ]);
    }
}
