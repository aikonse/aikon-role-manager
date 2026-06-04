<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab;
use WP_Roles;
use WP_UnitTestCase;

/**
 * Integration tests for CapabilitiesTab::handle_save_capabilities.
 *
 * Success paths are tested via $tab->handle() directly because
 * handle_save_capabilities does not redirect on success (unlike RolesTab).
 *
 * Cases that depend on real WordPress sanitization (sanitize_key stripping
 * special characters) are tested here rather than in the unit suite.
 */
class CapabilitiesTabTest extends WP_UnitTestCase
{
    private RoleManager $manager;

    public function set_up(): void
    {
        parent::set_up();

        global $wp_roles;
        $wp_roles = new WP_Roles();

        RoleManager::$instance = null;
        $this->manager = RoleManager::getInstance();

        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
    }

    public function tear_down(): void
    {
        RoleManager::$instance = null;

        foreach (['action', 'role', 'role_caps'] as $key) {
            unset($_POST[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tear_down();
    }

    /**
     * @param array<string, mixed> $data
     */
    private function post(array $data): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        foreach ($data as $key => $value) {
            $_POST[$key]    = $value;
            $_REQUEST[$key] = $value;
        }
    }

    // =========================================================================
    // Success paths
    // =========================================================================

    public function test_can_save_capabilities_for_a_role(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => ['read' => '1', 'edit_posts' => '0'],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertEmpty($tab->errors());
        $caps = $this->manager->current_roles()['custom-role']['capabilities'];
        $this->assertArrayHasKey('read', $caps);
        $this->assertArrayHasKey('edit_posts', $caps);
    }

    public function test_value_one_maps_to_true_other_values_map_to_false(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => ['read' => '1', 'edit_posts' => '0'],
        ]);

        (new CapabilitiesTab())->handle();

        $caps = $this->manager->current_roles()['custom-role']['capabilities'];
        $this->assertTrue($caps['read']);
        $this->assertFalse($caps['edit_posts']);
    }

    public function test_saving_replaces_previous_capabilities(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role', ['read' => true, 'delete_posts' => true]);

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => ['edit_posts' => '1'],
        ]);

        (new CapabilitiesTab())->handle();

        $caps = $this->manager->current_roles()['custom-role']['capabilities'];
        $this->assertArrayHasKey('edit_posts', $caps);
        $this->assertArrayNotHasKey('delete_posts', $caps);
    }

    // =========================================================================
    // Validation failures
    // =========================================================================

    public function test_save_capabilities_rejected_when_role_does_not_exist(): void
    {
        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'nonexistent-role',
            'role_caps' => ['read' => '1'],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertArrayHasKey('role', $tab->errors());
        // No role should have been modified
        $this->assertFalse($this->manager->role_exists('nonexistent-role'));
    }

    public function test_save_capabilities_rejected_when_role_field_is_whitespace_only(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        // sanitize_text_field trims whitespace to '', which fails the minlength:2
        // check in Request::validate(), so $request->get('role') returns false.
        $this->post([
            'action'    => 'save_capabilities',
            'role'      => '   ',
            'role_caps' => ['read' => '1'],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertArrayHasKey('role', $tab->errors());
    }

    public function test_save_capabilities_rejected_when_capabilities_array_is_empty(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => [],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertArrayHasKey('capabilities', $tab->errors());
        // Capabilities should be unchanged
        $this->assertEmpty($this->manager->current_roles()['custom-role']['capabilities']);
    }

    public function test_save_capabilities_throws_when_capability_key_sanitizes_to_too_short(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        // sanitize_key('a!') → 'a' (strips special chars) → length 1 → invalid
        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => ['a!' => '1'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid capability');

        (new CapabilitiesTab())->handle();
    }

    // =========================================================================
    // Access control
    // =========================================================================

    public function test_capabilities_tab_middleware_throws_for_user_without_manage_options(): void
    {
        $user_id = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($user_id);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['action']    = 'save_capabilities';
        $_REQUEST['action'] = 'save_capabilities';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('You do not have permission to access this page.');

        (new CapabilitiesTab())->handle();
    }
}
