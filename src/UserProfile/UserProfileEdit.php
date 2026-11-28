<?php

namespace Aikon\RoleManager\UserProfile;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\Request;

use function Aikon\RoleManager\template;

class UserProfileEdit
{
    /** Name of the nonce field the additional roles form submits */
    public const NONCE_FIELD = '_aikon_rm_other_roles_nonce';

    /** @var RoleManager */
    private $manager;

    protected string $form = 'aikon_role_manager_other_roles';

    public function __construct()
    {
        $this->manager = RoleManager::getInstance();

        add_action('show_user_profile', [$this, 'edit_user_profile'], 10, 1);
        add_action('edit_user_profile', [$this, 'edit_user_profile'], 10, 1);
        add_action('profile_update', [$this, 'handle_other_roles'], 10, 2);
    }

    /**
     * Check if the current user can change the roles of a user
     */
    private function can_edit_roles(int $user_id): bool
    {
        return current_user_can('promote_user', $user_id);
    }

    private function nonce_action(int $user_id): string
    {
        return 'aikon_role_manager_other_roles_' . $user_id;
    }

    /**
     * Roles the current user may add to, or remove from, a user.
     *
     * Mirrors core: only roles from get_editable_roles() (which honours the
     * editable_roles filter) can be changed. On your own profile you may only
     * add roles that grant nothing you don't already have, so you can't
     * promote yourself. Roles holding capabilities restricted to super admins
     * can't be changed by anyone else.
     *
     * @return array{add: string[], remove: string[]}
     */
    private function assignable_roles(int $user_id): array
    {
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        $roles = $this->manager->current_roles();

        $editable = array_values(array_filter(
            array_map('strval', array_keys(get_editable_roles())),
            fn (string $role): bool => isset($roles[$role]) &&
                !$this->manager->role_grants_restricted_capabilities($role)
        ));

        $can_add = $editable;

        if ($user_id === get_current_user_id()) {
            $can_add = array_values(array_filter(
                $editable,
                fn (string $role): bool => $this->grants_nothing_new($roles[$role]['capabilities'])
            ));
        }

        return [
            'add'    => $can_add,
            'remove' => $editable,
        ];
    }

    /**
     * Whether every capability granted by a role is one the current user already has.
     *
     * @param array<string, bool> $capabilities
     */
    private function grants_nothing_new(array $capabilities): bool
    {
        foreach ($capabilities as $cap => $granted) {
            // Legacy user level 0 means nothing beyond being a user. Roles created
            // through this plugin don't carry level caps, so don't let it block e.g.
            // adding "subscriber". Higher levels are still checked: old code gates on them.
            if ($cap === 'level_0') {
                continue;
            }

            if ($granted && !current_user_can($cap)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Render the user profile edit form
     *
     * @param \WP_User $user
     * @return void
     */
    public function edit_user_profile($user)
    {
        $user_roles   = $user->roles;
        $roles        = $this->manager->current_roles();
        $primary_role = array_shift($user_roles);
        $other_roles  = array_values(array_diff($user_roles, [$primary_role]));

        $can_edit_roles = $this->can_edit_roles($user->ID);
        $toggleable     = [];

        if ($can_edit_roles) {
            ['add' => $can_add, 'remove' => $can_remove] = $this->assignable_roles($user->ID);

            foreach (array_keys($roles) as $role) {
                $role         = (string) $role;
                $is_assigned  = in_array($role, $other_roles, true);
                if (in_array($role, $is_assigned ? $can_remove : $can_add, true)) {
                    $toggleable[] = $role;
                }
            }
        }

        template('userprofile-edit', [
            'primary_role'     => $primary_role,
            'roles'            => $roles,
            'user_other_roles' => $other_roles,
            'form'             => $this->form,
            'can_edit_roles'   => $can_edit_roles,
            'toggleable_roles' => $toggleable,
            'nonce_field'      => self::NONCE_FIELD,
            'nonce_action'     => $this->nonce_action($user->ID),
        ]);
    }

    /**
     * Handle saving user profile other roles
     *
     * Only acts on a submission of the additional roles form itself, so other
     * callers of wp_update_user() (REST API, other plugins) leave the user's
     * secondary roles alone.
     *
     * @param integer $user_id
     * @param mixed $old_user_data The user before the update, passed by profile_update
     * @return void
     */
    public function handle_other_roles(int $user_id, mixed $old_user_data = null)
    {
        $nonce = $_POST[self::NONCE_FIELD] ?? null;

        if (!is_string($nonce)) {
            return;
        }

        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($nonce)), $this->nonce_action($user_id))) {
            return;
        }

        if (!$this->can_edit_roles($user_id)) {
            return;
        }

        $user = get_userdata($user_id);
        if ($user === false) {
            return;
        }

        $request = new Request();
        $request->validate([
            $this->form => 'array:string'
        ]);

        /** @var string[] */
        $submitted = $request->get($this->form, []);
        $submitted = array_map('sanitize_text_field', $submitted);

        $current_roles = array_values($user->roles);
        $primary_role  = $current_roles[0] ?? null;

        if (!is_string($primary_role)) {
            return;
        }

        // Core may already have reset the user to a single role (set_role) before
        // profile_update fires, so start from the roles the user had before the save.
        $previous_roles = $old_user_data instanceof \WP_User
            ? array_values($old_user_data->roles)
            : $current_roles;
        $previous_other_roles = array_slice($previous_roles, 1);

        ['add' => $can_add, 'remove' => $can_remove] = $this->assignable_roles($user_id);

        $other_roles = [];

        // Keep roles the current user may not remove, and those still selected
        foreach ($previous_other_roles as $role) {
            if (!in_array($role, $can_remove, true) || in_array($role, $submitted, true)) {
                $other_roles[] = $role;
            }
        }

        // Add selected roles the current user may assign
        foreach ($submitted as $role) {
            if (in_array($role, $can_add, true)) {
                $other_roles[] = $role;
            }
        }

        $other_roles = array_values(array_unique(array_diff($other_roles, [$primary_role])));

        $this->manager->add_roles_to_user($user_id, $other_roles);
    }
}
