<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Manager;

use function Aikon\RoleManager\config;

use WP_Roles;

final class RoleManager
{
    /** @var self|null */
    public static $instance = null;

    protected WP_Roles $wp_roles;

    /**
     * @return void
     */
    private function __construct()
    {
        /**  @var WP_Roles */
        global $wp_roles;
        $this->wp_roles = $wp_roles;
    }

    private function __clone()
    {
    }

    /**
     * Get the instance of the RoleManager
     *
     * @return RoleManager
     */
    public static function getInstance(): RoleManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function store_roles(): void
    {
        update_option($this->wp_roles->role_key, $this->wp_roles->roles);
    }

    /**
     * Check if a role is a default role
     *
     * @param string $slug
     * @return boolean
     */
    public function is_default_role(string $slug): bool
    {

        /** @var ?(string|int)[] */
        static $defaults;

        if ($defaults === null) {
            $config_defaults = config('default_capabilities', []);

            $defaults = is_array($config_defaults)
                ? array_keys($config_defaults)
                : [];
        }

        return in_array($slug, $defaults);
    }

    /**
     * Validate role slug
     *
     * @param string $slug The slug to validate
     * @return string|null The validated slug or null if invalid
     */
    public function validate_role_slug(string $slug): ?string
    {
        $slug = sanitize_key($slug);
        return strlen($slug) > 2 ? $slug : null;
    }

    /**
     * Validate role name
     *
     * @param string $name
     * @return string|null
     */
    public function validate_role_name(string $name): ?string
    {
        $name = sanitize_text_field($name);
        return strlen($name) > 2 ? $name : null;
    }

    /**
     * Validate capability
     *
     * @param string $cap
     * @return string|null
     */
    public function validate_capability(string $cap): ?string
    {
        return $this->validate_role_slug($cap);
    }

    /**
     * Get the current roles
     *
     * @return array<string, array<string, array{name: string, capabilities: array<string>}>>
     */
    public function current_roles(): array
    {
        /** @var array<string, array<string, array{name: string, capabilities: array<string>}>> */
        return $this->wp_roles->roles;
    }

    /**
     * Check if a role exists
     *
     * @param string $role
     * @return boolean
     */
    public function role_exists(string $role): bool
    {
        return isset($this->current_roles()[$role]);
    }

    /**
     * Get the current role
     *
     * @param string $role
     * @param string $display_name
     * @param array<string,bool> $capabilities
     * @return void
     */
    public function add_role(string $role, string $display_name, array $capabilities = []): void
    {
        add_role($role, $display_name, $capabilities);
    }

    /**
     * Add an array of roles to a user
     *
     * @param integer $user_id
     * @param string[] $role
     * @return void
     */
    public function add_user_roles(int $user_id, array $role): void
    {
        $user = new \WP_User($user_id);
        foreach ($role as $r) {
            $user->add_role($r);
        }
    }

    /**
     * Update a role
     *
     * @param string $role
     * @param string $display_name
     * @param string $slug
     * @return void
     */
    public function update_role(string $role, string $display_name, string $slug): void
    {
        if (! isset($this->wp_roles->roles[$role])) {
            throw new \Exception('Role does not exist');
        }

        if ($role !== $slug && isset($this->wp_roles->roles[$slug])) {
            throw new \Exception('Role already exists');
        }

        $this->wp_roles->roles[$role]['name'] = $display_name;

        if ($role !== $slug) {
            $this->wp_roles->roles[$slug] = $this->wp_roles->roles[$role];
            unset($this->wp_roles->roles[$role]);

            // Update user roles
            /** @var \WP_User_Query */
            $query = new \WP_User_Query([
                'role' => $role,
            ]);

            /** @var \WP_User[] */
            $users = $query->get_results();

            foreach ($users as $user) {
                $user->remove_role($role);
                $user->add_role($slug);
            }
        }
        $this->store_roles();
    }

    /**
     * Remove a role
     *  - When removing a role from the system we also remove it from all users
     *
     * @param string $role
     * @return void
     */
    public function remove_role(string $role): void
    {
        remove_role($role);

        // Clean up users with this role
        /** @var \WP_User_Query */
        $query = new \WP_User_Query([
            'role' => $role,
        ]);

        /** @var \WP_User[] */
        $users = $query->get_results();

        foreach ($users as $user) {
            $user->remove_role($role);
            if (empty($user->roles)) {
                /** @var string */
                $default_role = get_option('default_role', 'subscriber');
                $user->add_role($default_role);
            }
        }
    }
}
