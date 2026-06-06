<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Manager;

final class SettingsManager
{
    public const OPTION_KEY = 'aikon_role_manager_settings';

    /** @var self|null */
    public static $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return array{protected_roles: string[], protected_post_types: string[]}
     */
    private function get_settings(): array
    {
        /** @var mixed $option */
        $option = get_option(self::OPTION_KEY, []);

        /** @var array{protected_roles: string[], protected_post_types: string[]} */
        $defaults = [
            'protected_roles'      => ['administrator'],
            'protected_post_types' => [],
        ];

        if (!is_array($option) || empty($option)) {
            return $defaults;
        }

        $protectedRoles = is_array($option['protected_roles'] ?? null)
            ? $option['protected_roles']
            : [];

        $protectedPostTypes = is_array($option['protected_post_types'] ?? null)
            ? $option['protected_post_types']
            : [];

        /** @var array{protected_roles: string[], protected_post_types: string[]} $option */
        $option = [
            'protected_roles' => array_filter($protectedRoles, 'is_string'),
            'protected_post_types' => array_filter($protectedPostTypes, 'is_string'),
        ];

        return $option;
    }

    /**
     * @return string[]
     */
    public function get_protected_roles(): array
    {
        return $this->get_settings()['protected_roles'];
    }

    /**
     * @return string[]
     */
    public function get_protected_post_types(): array
    {
        return $this->get_settings()['protected_post_types'];
    }

    /**
     * @param string[] $roles
     */
    public function set_protected_roles(array $roles): void
    {
        $settings                    = $this->get_settings();
        $settings['protected_roles'] = array_values(array_filter(array_map('sanitize_key', $roles)));
        update_option(self::OPTION_KEY, $settings, true);
    }

    /**
     * @param string[] $post_types
     */
    public function set_protected_post_types(array $post_types): void
    {
        $settings                         = $this->get_settings();
        $settings['protected_post_types'] = array_values(array_filter(array_map('sanitize_key', $post_types)));
        update_option(self::OPTION_KEY, $settings, true);
    }

    /**
     * Returns true if the role is allowed to be changed (not protected).
     */
    public function change_role(string $role): bool
    {
        return !in_array($role, $this->get_protected_roles(), true);
    }

    /**
     * Returns true if the post type capability override is allowed to be changed (not protected).
     */
    public function change_post_type(string $post_type): bool
    {
        return !in_array($post_type, $this->get_protected_post_types(), true);
    }
}
