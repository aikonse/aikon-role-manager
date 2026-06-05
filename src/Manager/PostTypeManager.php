<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Manager;

final class PostTypeManager
{
    public const OPTION_KEY = 'aikon_post_type_capability_overrides';

    /** @var self|null */
    public static $instance = null;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function getInstance(): PostTypeManager
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @return array<string, string>
     */
    public function get_overrides(): array
    {
        /** @var array<string, string>|false $option */
        $option = get_option(self::OPTION_KEY, []);

        return is_array($option) ? $option : [];
    }

    public function set_override(string $post_type, string $capability_type): void
    {
        $overrides                = $this->get_overrides();
        $overrides[$post_type]    = $capability_type;
        update_option(self::OPTION_KEY, $overrides, true);
    }

    public function remove_override(string $post_type): void
    {
        $overrides = $this->get_overrides();
        unset($overrides[$post_type]);
        update_option(self::OPTION_KEY, $overrides, true);
    }

    public function has_override(string $post_type): bool
    {
        return isset($this->get_overrides()[$post_type]);
    }

    /**
     * Validate a capability_type value — must be a non-empty lowercase slug.
     */
    public function validate_capability_type(mixed $value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $value = sanitize_key($value);

        return strlen($value) > 0 ? $value : null;
    }

    /**
     * Register the register_post_type_args filter that applies overrides.
     * Call this early (e.g. plugins_loaded) so it fires before init.
     */
    public function apply_overrides(): void
    {
        add_filter('register_post_type_args', function (array $args, string $post_type): array {
            $overrides = $this->get_overrides();

            if (isset($overrides[$post_type])) {
                $args['capability_type'] = $overrides[$post_type];
                unset($args['capabilities']);
            }

            return $args;
        }, 5, 2);
    }
}
