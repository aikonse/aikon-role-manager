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
        $this->post_action('action', 'update_role', [$this, 'handle_update_role']);
        $this->get_action('action', 'delete_role', [$this, 'handle_delete_role']);
    }

    /**
     * Add a role
     *
     * @param array<string, string> $request
     * @return void
     */
    public function handle_add_role(array $request): void
    {
        $name = $this->manager->validate_role_name($request['name'] ?? '');
        $slug = $this->manager->validate_role_slug($request['slug'] ?? '');

        if (!$name) {
            $this->add_notice(__('Role name is required', 'aikon-role-manager'), 'warning');
            $this->add_error('name', __('Role name is required', 'aikon-role-manager'));
        }

        if (!$slug) {
            $this->add_notice(__('Role slug is required', 'aikon-role-manager'), 'warning');
            $this->add_error('slug', __('Role slug is required', 'aikon-role-manager'));
        }

        if (!$name || !$slug) {
            return;
        }

        if (isset($this->manager->current_roles()[$slug])) {
            $this->add_notice(__('Role already exists', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->add_role( $slug,  $name,);
        wp_redirect(url_parser(['tab' => $this->slug]));
        exit;
    }

    /**
     * Update a role
     *
     * @param array<string, string> $request
     * @return void
     */
    public function handle_update_role(array $request): void
    {
        $updating = $this->manager->validate_role_slug($request['edit_role'] ?? '');
        $role = $this->manager->validate_role_name($request['name'] ?? '');
        $slug = $this->manager->validate_role_slug($request['slug'] ?? '');

        // Trying to update a role that does not exist
        if (
            !$updating ||
            !$this->manager->role_exists($updating)
        ) {
            $this->add_notice(__('Role does not exist', 'aikon-role-manager'), 'error');
            return;
        }

        if (!$role) {
            $this->add_notice(__('Role name is required', 'aikon-role-manager'), 'warning');
            $this->add_error('name', __('Role name is required', 'aikon-role-manager'));
        }

        if (!$slug) {
            $this->add_notice(__('Role slug is required', 'aikon-role-manager'), 'warning');
            $this->add_error('slug', __('Role slug is required', 'aikon-role-manager'));
        }

        if (!$role || !$slug) {
            return;
        }

        if (
            $updating !== $slug &&
            $this->manager->role_exists($slug)
        ) {
            $this->add_notice(__('Role already exists', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->update_role($updating, $slug, $role);
        $this->add_notice(__('Role updated', 'aikon-role-manager'), 'success');
    }

    private function handle_delete_role($request)
    {
        $role_slug = $this->manager->validate_role_slug($request['delete_role'] ?? '');


        if (
            !$role_slug ||
            !$this->manager->role_exists($role_slug)
        ) {
            $this->add_notice(__('Role does not exist', 'aikon-role-manager'), 'warning');
            return;
        }

        if ($this->manager->is_default_role($role_slug)) {
            $this->add_notice(__('You cannot delete a default role', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->remove_role($role_slug);
        $this->add_notice(__('Role deleted', 'aikon-role-manager'), 'success');

        wp_redirect(url_parser([], ['delete_role', 'action']));
        exit;
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
