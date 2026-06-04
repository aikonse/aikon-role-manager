<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\OptionsPage\Tabs\RolesTab;
use WP_UnitTestCase;

/**
 * Tests that the options page enforces capability-based access control.
 *
 * The page is registered under the 'manage_options' capability via add_users_page().
 * RolesTab::handle() also explicitly checks the capability via middleware before
 * processing any action — but that middleware only runs when a matching request fires.
 */
class OptionsPageAccessTest extends WP_UnitTestCase
{
    public function set_up(): void
    {
        parent::set_up();

        // add_users_page() lives in wp-admin/includes/plugin.php, which is not
        // loaded by the WP test bootstrap (CLI context, not a web request).
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    public function tear_down(): void
    {
        // Clean up any simulated request state
        unset($_POST['action']);
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tear_down();
    }

    // -------------------------------------------------------------------------
    // Menu registration
    // -------------------------------------------------------------------------

    public function test_options_page_is_registered_in_users_submenu(): void
    {
        global $submenu;

        // add_users_page() resolves to users.php only when the current user has
        // edit_users — without a logged-in admin it falls back to profile.php.
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        do_action('admin_menu');

        $this->assertIsArray($submenu, 'Expected $submenu to be populated after admin_menu');
        $this->assertArrayHasKey('users.php', $submenu, 'Expected a submenu under users.php');

        $slugs = array_column($submenu['users.php'], 2);
        $this->assertContains('aikon-role-manager', $slugs, 'Expected aikon-role-manager in the users submenu');
    }

    public function test_options_page_requires_manage_options_capability(): void
    {
        global $submenu;

        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        do_action('admin_menu');

        $entry = array_filter(
            $submenu['users.php'] ?? [],
            fn (array $item) => ($item[2] ?? '') === 'aikon-role-manager'
        );

        $this->assertNotEmpty($entry, 'Menu entry for aikon-role-manager not found');
        $this->assertSame('manage_options', array_values($entry)[0][1]);
    }

    // -------------------------------------------------------------------------
    // Capability checks per role
    // -------------------------------------------------------------------------

    public function test_administrator_has_manage_options_capability(): void
    {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);

        $this->assertTrue(current_user_can('manage_options'));
    }

    public function test_editor_does_not_have_manage_options_capability(): void
    {
        $user_id = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($user_id);

        $this->assertFalse(current_user_can('manage_options'));
    }

    public function test_subscriber_does_not_have_manage_options_capability(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        wp_set_current_user($user_id);

        $this->assertFalse(current_user_can('manage_options'));
    }

    public function test_unauthenticated_user_does_not_have_manage_options_capability(): void
    {
        wp_set_current_user(0);

        $this->assertFalse(current_user_can('manage_options'));
    }

    // -------------------------------------------------------------------------
    // Tab-level enforcement via middleware
    //
    // RolesTab::handle() registers a middleware that checks manage_options, but
    // the middleware only executes when a matching POST/GET action is dispatched.
    // We simulate a POST request to trigger it.
    // -------------------------------------------------------------------------

    public function test_roles_tab_middleware_throws_for_user_without_manage_options(): void
    {
        $user_id = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($user_id);

        // Simulate a POST request that would trigger the middleware
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'add_role';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to manage roles and capabilities');

        (new RolesTab())->handle();
    }

    public function test_roles_tab_middleware_does_not_throw_for_administrator(): void
    {
        $user_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($user_id);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action'] = 'add_role';

        // The middleware passes for admins — subsequent processing may fail for
        // other reasons (missing form fields), but not due to capability denial.
        try {
            (new RolesTab())->handle();
        } catch (\Exception $e) {
            $this->assertStringNotContainsString(
                'You do not have permission',
                $e->getMessage(),
                'Admin should not be blocked by the capability middleware'
            );
        }

        // If no exception at all, the test passes cleanly
        $this->addToAssertionCount(1);
    }
}
