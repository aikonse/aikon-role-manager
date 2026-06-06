<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\Manager\SettingsManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HandlesActions;
use Aikon\RoleManager\OptionsPage\Traits\HandlesNotice;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;
use Aikon\RoleManager\Request;

use function Aikon\RoleManager\template;
use function Aikon\RoleManager\url_parser;

class SettingsTab implements TabInterface
{
    use HasTitleAnSlug;
    use HandlesNotice;
    use HandlesActions;

    private SettingsManager $manager;

    public function __construct()
    {
        $this->title   = __('Settings', 'aikon-role-manager');
        $this->slug    = 'settings';
        $this->icon    = 'dashicons-admin-settings';
        $this->manager = SettingsManager::getInstance();
    }

    public function visible(): bool
    {
        return in_array('administrator', (array) wp_get_current_user()->roles, true);
    }

    public function handle(): void
    {
        $this->middleware(function (Request $request): Request {
            if (!$this->visible()) {
                wp_die(
                    esc_html__('You do not have permission to manage settings.', 'aikon-role-manager'),
                    '',
                    ['response' => 401]
                );
            }
            return $request;
        });

        $this->post_action('action', 'save_settings', [$this, 'handle_save_settings']);
    }

    private function handle_save_settings(Request $request): void
    {
        $role_manager = RoleManager::getInstance();
        $valid_roles  = array_keys($role_manager->current_roles());

        $submitted_roles = $request->get('protected_roles', []);

        $submitted_roles = is_array($submitted_roles)
            ? $submitted_roles
            : [];

        $submitted_roles = array_filter($submitted_roles, function ($role) {
            return is_string($role);
        });

        $protected_roles = [];

        foreach ($submitted_roles as $role) {
            $role = sanitize_key((string) $role);
            if ($role && in_array($role, $valid_roles, true)) {
                $protected_roles[] = $role;
            }
        }

        /** @var array<string,bool> */
        $pt_args = ['show_ui' => true];
        $valid_post_types = array_keys(get_post_types($pt_args));

        $submitted_post_types = $request->get('protected_post_types', []);

        $submitted_post_types = is_array($submitted_post_types)
            ? $submitted_post_types
            : [];

        $submitted_post_types = array_filter($submitted_post_types, function ($pt) {
            return is_string($pt);
        });

        $protected_post_types = [];

        foreach ($submitted_post_types as $pt) {
            $pt = sanitize_key((string) $pt);
            if ($pt && in_array($pt, $valid_post_types, true)) {
                $protected_post_types[] = $pt;
            }
        }

        $this->manager->set_protected_roles($protected_roles);
        $this->manager->set_protected_post_types($protected_post_types);

        $this->add_notice(__('Settings saved', 'aikon-role-manager'), 'success');

        wp_redirect(url_parser(['tab' => $this->slug], ['action']));
        exit;
    }

    public function render(): void
    {
        $role_manager = RoleManager::getInstance();
        $roles        = $role_manager->current_roles();

        /** @var array<string,bool> */
        $pt_args    = ['show_ui' => true];
        $post_types = get_post_types($pt_args, 'objects');

        template('tab-settings', [
            'roles'                => $roles,
            'post_types'           => $post_types,
            'protected_roles'      => $this->manager->get_protected_roles(),
            'protected_post_types' => $this->manager->get_protected_post_types(),
        ]);
    }
}
