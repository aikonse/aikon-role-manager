<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Unit\OptionsPage;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\Manager\SettingsManager;
use Aikon\RoleManager\OptionsPage\Tabs\RolesTab;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RolesTab update-role form validation.
 *
 * These cover cases where the constraint can be verified without real WordPress
 * (length checks, conflict detection). Cases that depend on real WP sanitization
 * behaviour — sanitize_text_field stripping whitespace, sanitize_key removing
 * special characters — live in the Integration suite instead, because they
 * assert the output of actual WP functions rather than our own code.
 *
 * RoleManager is a final singleton, so it cannot be subclassed or mocked by
 * Mockery. Instead we inject a minimal $wp_roles stub into the global and let
 * a real RoleManager instance be created; WP functions are mocked via Brain Monkey.
 */
class RolesTabTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // Translation passthrough — RolesTab calls __() in error messages
        Functions\when('__')->returnArg(1);

        // Stub $wp_roles so RoleManager::__construct() has something to bind to.
        // Only the properties accessed by role_exists() and validate_role_slug/name
        // need to be present.
        global $wp_roles;
        $wp_roles = new \WP_Roles();
        $wp_roles->roles = [
            'administrator' => ['name' => 'Administrator', 'capabilities' => []],
            'existing-role' => ['name' => 'Existing Role', 'capabilities' => []],
        ];

        RoleManager::$instance    = null;
        SettingsManager::$instance = null;
    }

    protected function tearDown(): void
    {
        RoleManager::$instance    = null;
        SettingsManager::$instance = null;

        global $wp_roles;
        $wp_roles = null;

        foreach (['action', 'role', 'name', 'slug'] as $key) {
            unset($_POST[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Simulate a POST request. Both $_POST and $_REQUEST must be set because
     * Request::__construct() reads from $_REQUEST.
     *
     * @param array<string,string> $data
     */
    private function post(array $data): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        foreach ($data as $key => $value) {
            $_POST[$key] = $value;
            $_REQUEST[$key] = $value;
        }
    }

    /**
     * Stub the WP functions that every POST through handle_update_role touches:
     *   - sanitize_text_field: called by post_action() to read the action key
     *   - current_user_can:    checked by the capability middleware
     *   - sanitize_key:        called by validate_role_slug()
     */
    private function stubWpFunctions(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sanitize_key')->returnArg(1);
        // SettingsManager::get_settings() calls get_option(); returning false
        // triggers the defaults (administrator protected, no protected post types).
        // The roles under test ('existing-role', 'ab') are not administrator, so
        // change_role() returns true and the protection check passes through.
        Functions\when('get_option')->justReturn(false);
    }

    // =========================================================================
    // slug length constraint
    // =========================================================================

    public function test_update_role_rejected_when_slug_is_too_short(): void
    {
        $this->stubWpFunctions();

        $this->post([
            'action' => 'update_role',
            'role'   => 'existing-role',
            'name'   => 'Valid Name',
            'slug'   => 'ab',           // 2 chars — validate_role_slug requires > 2
        ]);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('slug', $tab->errors());
    }

    public function test_update_role_rejected_when_both_name_and_slug_are_too_short(): void
    {
        $this->stubWpFunctions();

        $this->post([
            'action' => 'update_role',
            'role'   => 'existing-role',
            'name'   => 'ab',
            'slug'   => 'ab',
        ]);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('name', $tab->errors());
        $this->assertArrayHasKey('slug', $tab->errors());
    }

    // =========================================================================
    // new slug conflicts with an existing role
    // =========================================================================

    public function test_update_role_rejected_when_new_slug_is_already_taken(): void
    {
        $this->stubWpFunctions();

        // 'existing-role' is being renamed to 'administrator' — which already exists
        $this->post([
            'action' => 'update_role',
            'role'   => 'existing-role',
            'name'   => 'Existing Role',
            'slug'   => 'administrator',
        ]);

        $tab = new RolesTab();
        $tab->handle();

        // Conflict reported via notice — no tab errors, both roles untouched
        $this->assertEmpty($tab->errors());
        $this->assertArrayHasKey('existing-role', RoleManager::getInstance()->current_roles());
        $this->assertArrayHasKey('administrator', RoleManager::getInstance()->current_roles());
    }

    // =========================================================================
    // role identifier is invalid
    // =========================================================================

    public function test_update_role_rejected_when_role_param_is_too_short(): void
    {
        $this->stubWpFunctions();

        $this->post([
            'action' => 'update_role',
            'role'   => 'ab',           // too short — validate_role_slug returns null → "does not exist"
            'name'   => 'Valid Name',
            'slug'   => 'valid-slug',
        ]);

        $tab = new RolesTab();
        $tab->handle();

        // Returns early with "Role does not exist" notice — no tab errors
        $this->assertEmpty($tab->errors());
    }
}
