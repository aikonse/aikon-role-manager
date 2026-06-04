<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Tabs\RolesTab;
use WP_Roles;
use WP_UnitTestCase;
use WP_User;

/**
 * Tests for adding and editing roles via RolesTab and RoleManager.
 *
 * Success paths (add/update) are tested directly on RoleManager because
 * RolesTab calls wp_redirect() + exit on success, which would terminate
 * the test process. Validation/rejection paths are tested through RolesTab
 * via simulated POST requests, since those return early without redirecting.
 */
class RolesTabTest extends WP_UnitTestCase
{
    private RoleManager $manager;

    public function set_up(): void
    {
        parent::set_up();

        // WP test suite rolls back DB between tests via transactions, but
        // $wp_roles->roles in memory is NOT rolled back. Reinitialising
        // WP_Roles from the DB gives each test a clean role state.
        global $wp_roles;
        $wp_roles = new WP_Roles();

        // Reset singleton so it binds to the freshly initialised $wp_roles.
        RoleManager::$instance = null;
        $this->manager = RoleManager::getInstance();

        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);
    }

    public function tear_down(): void
    {
        RoleManager::$instance = null;

        // Clean up simulated request state from both $_POST and $_REQUEST.
        foreach (['action', 'name', 'slug', 'role'] as $key) {
            unset($_POST[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tear_down();
    }

    /**
     * Simulates a POST request. Must set both $_POST and $_REQUEST because
     * Request::__construct() reads from $_REQUEST, and in CLI PHP these
     * superglobals are not automatically kept in sync.
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

    // =========================================================================
    // Adding roles — success paths (tested via RoleManager directly)
    // =========================================================================

    public function test_can_add_a_new_role(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->assertTrue($this->manager->role_exists('custom-role'));
    }

    public function test_added_role_appears_in_current_roles_with_correct_name(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $roles = $this->manager->current_roles();
        $this->assertArrayHasKey('custom-role', $roles);
        $this->assertSame('Custom Role', $roles['custom-role']['name']);
    }

    public function test_added_role_includes_provided_capabilities(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role', ['read' => true, 'edit_posts' => false]);

        $capabilities = $this->manager->current_roles()['custom-role']['capabilities'];
        $this->assertArrayHasKey('read', $capabilities);
        $this->assertArrayHasKey('edit_posts', $capabilities);
        $this->assertTrue($capabilities['read']);
        $this->assertFalse($capabilities['edit_posts']);
    }

    public function test_new_role_does_not_exist_before_being_added(): void
    {
        $this->assertFalse($this->manager->role_exists('brand-new-role'));
    }

    // =========================================================================
    // Adding roles — validation failures (tested via RolesTab POST)
    // =========================================================================

    public function test_add_role_form_rejected_when_name_is_too_short(): void
    {
        $roleCountBefore = count($this->manager->current_roles());

        $this->post(['action' => 'add_role', 'name' => 'ab', 'slug' => 'valid-slug']);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('name', $tab->errors());
        $this->assertSame($roleCountBefore, count($this->manager->current_roles()));
    }

    public function test_add_role_form_rejected_when_slug_is_too_short(): void
    {
        $roleCountBefore = count($this->manager->current_roles());

        $this->post(['action' => 'add_role', 'name' => 'Valid Name', 'slug' => 'ab']);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('slug', $tab->errors());
        $this->assertSame($roleCountBefore, count($this->manager->current_roles()));
    }

    public function test_add_role_form_rejected_when_both_name_and_slug_too_short(): void
    {
        $roleCountBefore = count($this->manager->current_roles());

        $this->post(['action' => 'add_role', 'name' => 'ab', 'slug' => 'ab']);

        $tab = new RolesTab();
        $tab->handle();

        $errors = $tab->errors();
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('slug', $errors);
        $this->assertSame($roleCountBefore, count($this->manager->current_roles()));
    }

    public function test_add_role_form_rejected_when_role_already_exists(): void
    {
        $roleCountBefore = count($this->manager->current_roles());

        $this->post(['action' => 'add_role', 'name' => 'Administrator', 'slug' => 'administrator']);

        $tab = new RolesTab();
        $tab->handle();

        // Duplicate is caught with a notice — no tab errors, no new role
        $this->assertEmpty($tab->errors());
        $this->assertSame($roleCountBefore, count($this->manager->current_roles()));
    }

    // =========================================================================
    // Editing roles — success paths (tested via RoleManager directly)
    // =========================================================================

    public function test_can_update_role_display_name(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->manager->update_role('custom-role', 'Renamed Role', 'custom-role');

        $this->assertSame('Renamed Role', $this->manager->current_roles()['custom-role']['name']);
    }

    public function test_can_rename_role_slug(): void
    {
        $this->manager->add_role('old-slug', 'My Role');

        $this->manager->update_role('old-slug', 'My Role', 'new-slug');

        $this->assertFalse($this->manager->role_exists('old-slug'));
        $this->assertTrue($this->manager->role_exists('new-slug'));
    }

    public function test_renaming_slug_migrates_existing_users_to_new_role(): void
    {
        $this->manager->add_role('old-slug', 'My Role');
        $user_id = self::factory()->user->create(['role' => 'old-slug']);

        $this->manager->update_role('old-slug', 'My Role', 'new-slug');

        clean_user_cache($user_id);
        $user = new WP_User($user_id);
        $this->assertContains('new-slug', $user->roles);
        $this->assertNotContains('old-slug', $user->roles);
    }

    public function test_update_role_preserves_capabilities_when_renaming_slug(): void
    {
        $this->manager->add_role('old-slug', 'My Role', ['read' => true]);

        $this->manager->update_role('old-slug', 'My Role', 'new-slug');

        $this->assertArrayHasKey('read', $this->manager->current_roles()['new-slug']['capabilities']);
    }

    public function test_update_role_throws_for_nonexistent_role(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Role does not exist');

        $this->manager->update_role('nonexistent-role', 'Name', 'nonexistent-role');
    }

    public function test_update_role_throws_when_new_slug_already_taken(): void
    {
        $this->manager->add_role('role-one', 'Role One');
        $this->manager->add_role('role-two', 'Role Two');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Role already exists');

        $this->manager->update_role('role-one', 'Role One', 'role-two');
    }

    // =========================================================================
    // Editing roles — validation failures (tested via RolesTab POST)
    // =========================================================================

    public function test_update_role_form_rejected_when_role_does_not_exist(): void
    {
        $this->post(['action' => 'update_role', 'role' => 'nonexistent-role', 'name' => 'Some Name', 'slug' => 'some-name']);

        $tab = new RolesTab();
        $tab->handle();

        // Returns early with a notice — no errors on the tab
        $this->assertEmpty($tab->errors());
        $this->assertFalse($this->manager->role_exists('nonexistent-role'));
    }

    public function test_update_role_form_rejected_when_name_is_whitespace_only(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        // sanitize_text_field trims whitespace to an empty string, which is
        // then too short for validate_role_name — this verifies real WP
        // sanitization is applied before the length check.
        $this->post(['action' => 'update_role', 'role' => 'custom-role', 'name' => '   ', 'slug' => 'custom-role']);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('name', $tab->errors());
        $this->assertSame('Custom Role', $this->manager->current_roles()['custom-role']['name']);
    }

    public function test_update_role_form_rejected_when_slug_sanitizes_to_too_short(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        // sanitize_key strips non-alphanumeric chars: 'a!' becomes 'a' (1 char),
        // which fails the > 2 length check in validate_role_slug.
        $this->post(['action' => 'update_role', 'role' => 'custom-role', 'name' => 'Custom Role', 'slug' => 'a!']);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('slug', $tab->errors());
        $this->assertTrue($this->manager->role_exists('custom-role'));
    }

    public function test_update_role_form_rejected_when_name_is_too_short(): void
    {
        $this->manager->add_role('custom-role', 'Custom Role');

        $this->post(['action' => 'update_role', 'role' => 'custom-role', 'name' => 'ab', 'slug' => 'custom-role']);

        $tab = new RolesTab();
        $tab->handle();

        $this->assertArrayHasKey('name', $tab->errors());
        $this->assertSame('Custom Role', $this->manager->current_roles()['custom-role']['name']);
    }
}
