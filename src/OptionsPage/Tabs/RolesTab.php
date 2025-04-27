<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HandlesActions;
use Aikon\RoleManager\OptionsPage\Traits\HandlesNotice;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;
use Aikon\RoleManager\Request;

use function Aikon\RoleManager\template;
use function Aikon\RoleManager\url_parser;

class RolesTab implements TabInterface
{
    use HasTitleAnSlug;
    use HandlesNotice;
    use HandlesActions;

    private RoleManager $manager;

    public function __construct()
    {
        $this->title = __('Manage roles', 'aikon-role-manager');
        $this->slug = 'roles';
        $this->icon = 'dashicons-open-folder';

        $this->manager = RoleManager::getInstance();
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
     * @param Request $request
     * @return void
     */
    public function handle_add_role(Request $request): void
    {

        $request->validate([
            'name' => 'string|minlength:2',
            'slug' => 'string|minlength:2',
        ]);

        $name = $this->manager->validate_role_name($request->get('name'));
        $slug = $this->manager->validate_role_slug($request->get('slug'));

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

        $this->manager->add_role($slug, $name, );
        $this->add_notice(__('Role added', 'aikon-role-manager'), 'success');
        wp_redirect(url_parser(['tab' => $this->slug]));
        exit;
    }

    /**
     * Update a role
     *
     * @param Request $request
     * @return void
     */
    public function handle_update_role(Request $request): void
    {
        $request->validate([
            'role' => 'string|minlength:2',
            'name' => 'string|minlength:2',
            'slug' => 'string|minlength:2',
        ]);

        $updating   = $this->manager->validate_role_slug($request->get('role'));
        $name       = $this->manager->validate_role_name($request->get('name'));
        $slug       = $this->manager->validate_role_slug($request->get('slug', $updating)); // Use the current slug if not provided

        // Trying to update a role that does not exist
        if (
            !$updating ||
            !$this->manager->role_exists($updating)
        ) {
            $this->add_notice(__('Role does not exist', 'aikon-role-manager'), 'error');
            return;
        }

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

        if (
            $updating !== $slug &&
            $this->manager->role_exists($slug)
        ) {
            $this->add_notice(__('Role already exists', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->update_role($updating, $name, $slug);
        $this->add_notice(__('Role updated', 'aikon-role-manager'), 'success');

        wp_redirect(url_parser([], ['edit_role', 'action', 'role']));
        exit;
    }

    /**
     * Delete a role
     *
     * @param Request $request
     * @return void
     */
    private function handle_delete_role($request)
    {
        $request->validate(['delete_role' => 'string|minlength:2']);
        $role_slug = $this->manager->validate_role_slug($request->get('delete_role'));

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
        // View roles
        $template = 'tab-roles-view';
        $args = [
           'tab' => $this->slug,
           'roles' => $this->manager->current_roles(),
           'manager' => $this->manager,
           'view' => $this,
           'errors' => $this->errors(),
        ];

        // Edit role
        $edit_role = (isset($_GET['edit_role']) && is_string($_GET['edit_role']))
            ? sanitize_text_field($_GET['edit_role'])
            : false;

        if ($edit_role !== false) {
            $edit_role = $this->manager->validate_role_slug($edit_role);
        }

        if (
            is_string($edit_role) &&
            $this->manager->validate_role_slug($edit_role) &&
            $this->manager->role_exists($edit_role)
        ) {
            $template = 'tab-roles-edit';
            $role_slug = $edit_role;
            $args = [
                'slug'       => $role_slug,
                'name'       => $this->manager->current_roles()[$role_slug]['name'],
                'is_default' => $this->manager->is_default_role($role_slug),
                'errors'     => $this->errors(),
            ];
        }

        template($template, $args);
    }
}
