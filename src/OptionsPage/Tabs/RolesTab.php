<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HandlesActions;
use Aikon\RoleManager\OptionsPage\Traits\HandlesNotice;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;

use function Aikon\RoleManager\template;

class RolesTab implements TabInterface
{
    use HasTitleAnSlug;
    use HandlesNotice;
    use HandlesActions;

    public function __construct(
        private RoleManager $manager
    ) {
        $this->title = __('Manage roles', 'aikon-role-manager');
        $this->slug = 'roles';
        $this->icon = 'dashicons-admin-users';
    }

    public function handle(): void
    {
        $this->middleware(function ($request): void {
            if (!current_user_can('manage_options')) {
                throw new \Exception('You do not have permission to manage roles and capabilities');
            }
        });

        $this->post_action('action', 'add_role', [$this, 'handle_add_role']);

    }

    /**
     * Add a role
     *
     * @param array<string, string> $request
     * @return void
     */
    public function handle_add_role(array $request): void
    {
        $role = $this->manager->validate_role_name($request['role'] ?? '');
        $slug = $this->manager->validate_role_slug($request['slug'] ?? '');

        if (!$role) {
            $this->add_notice(__('Role name is required', 'aikon-role-manager'), 'error');
        }

        if (!$slug) {
            $this->add_notice(__('Role slug is required', 'aikon-role-manager'), 'error');
        }

        if (!$role || !$slug) {
            return;
        }

        if (isset($this->manager->current_roles()[$slug])) {
            $this->add_notice(__('Role already exists', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->add_role($slug, $role);
        $this->add_notice(__('Role added', 'aikon-role-manager'), 'success');
    }

    public function render(): void
    {
        template('tab-roles-view', [
            'tab' => $this->slug,
            'roles' => $this->manager->current_roles(),
            'manager' => $this->manager,
        ]);
    }
}
