<?php

/**
 * Stubs for the WordPress PHPUnit test framework.
 *
 * These exist solely for IDE type-checking (Intelephense). They are NOT
 * loaded at runtime — the real classes come from the WordPress test suite
 * inside the wp-env tests-cli container.
 */

// phpcs:disable

if (false) {
    /**
     * @method static \WP_UnitTest_Factory factory()
     */
    class WP_UnitTestCase extends \PHPUnit\Framework\TestCase
    {
        /**
         * @return \WP_UnitTest_Factory
         */
        public static function factory(): \WP_UnitTest_Factory
        {
        }

        public function set_up(): void
        {
        }

        public function tear_down(): void
        {
        }
    }

    class WP_UnitTest_Factory
    {
        /** @var \WP_UnitTest_Factory_For_User */
        public \WP_UnitTest_Factory_For_User $user;
    }

    class WP_UnitTest_Factory_For_User
    {
        /**
         * @param array<string, mixed> $args
         * @return int
         */
        public function create(array $args = []): int
        {
        }
    }
}
