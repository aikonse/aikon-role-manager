<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\UserProfile;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\UserProfile\UserProfileEdit;
use WP_Roles;
use WP_UnitTestCase;
use WP_User;

/**
 * Integration tests for UserProfileEdit::handle_other_roles.
 *
 * Tests verify that an authorised user (promote_users capability) can assign
 * and remove secondary roles, that unknown roles are silently filtered out,
 * and that unauthorised users cannot make any changes.
 *
 * The constructor registers WP action hooks; we call handle_other_roles()
 * directly so hook registration is irrelevant to the assertions.
 */
class UserProfileEditTest extends WP_UnitTestCase
{
    private RoleManager $manager;
    private UserProfileEdit $profile_edit;

    /** Matches the $form property on UserProfileEdit */
    private string $form = 'aikon_role_manager_other_roles';

    public function set_up(): void
    {
        parent::set_up();

        global $wp_roles;
        $wp_roles = new WP_Roles();

        RoleManager::$instance = null;
        $this->manager = RoleManager::getInstance();

        // Acting user must have promote_users so the capability check passes
        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $this->profile_edit = new UserProfileEdit();
    }

    public function tear_down(): void
    {
        RoleManager::$instance = null;
        unset($_REQUEST[$this->form]);
        parent::tear_down();
    }

    /**
     * Simulate the roles submitted via the profile form.
     *
     * @param string[] $roles
     */
    private function request(array $roles): void
    {
        $_REQUEST[$this->form] = $roles;
    }

    private function fresh_user(int $user_id): WP_User
    {
        clean_user_cache($user_id);
        return new WP_User($user_id);
    }

    // =========================================================================
    // Adding roles
    // =========================================================================

    public function test_can_add_a_secondary_role_to_a_user(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->request(['editor']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('subscriber', $roles);
        $this->assertContains('editor', $roles);
    }

    public function test_can_add_multiple_roles_to_a_user(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->request(['editor', 'author']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('subscriber', $roles);
        $this->assertContains('editor', $roles);
        $this->assertContains('author', $roles);
    }

    // =========================================================================
    // Removing roles
    // =========================================================================

    public function test_can_remove_a_secondary_role_by_omitting_it_from_the_request(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $user = new WP_User($user_id);
        $user->add_role('editor');

        // Re-submit with only 'author' — 'editor' should be removed
        $this->request(['author']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('author', $roles);
        $this->assertNotContains('editor', $roles);
    }

    public function test_submitting_empty_array_removes_all_secondary_roles(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $user = new WP_User($user_id);
        $user->add_role('editor');

        $this->request([]);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertCount(1, $roles);
        $this->assertContains('subscriber', $roles);
    }

    // =========================================================================
    // Role filtering
    // =========================================================================

    public function test_nonexistent_role_is_silently_filtered_out(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->request(['made-up-role', 'editor']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertNotContains('made-up-role', $roles);
        $this->assertContains('editor', $roles);
    }

    public function test_only_currently_registered_roles_can_be_assigned(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);

        // Register a role, then unregister it — it should no longer be assignable
        $this->manager->add_role('temp-role', 'Temp Role');
        remove_role('temp-role');
        global $wp_roles;
        $wp_roles = new WP_Roles();
        RoleManager::$instance = null;
        $this->profile_edit = new UserProfileEdit();

        $this->request(['temp-role']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertNotContains('temp-role', $roles);
    }

    // =========================================================================
    // Access control
    // =========================================================================

    public function test_handle_other_roles_does_nothing_when_user_lacks_promote_users(): void
    {
        // Switch to an editor who does not have promote_users
        $editor_id = self::factory()->user->create(['role' => 'editor']);
        wp_set_current_user($editor_id);

        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->request(['administrator']);

        $this->profile_edit->handle_other_roles($user_id);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertNotContains('administrator', $roles);
        $this->assertCount(1, $roles);
    }

    // =========================================================================
    // Error handling
    // =========================================================================

    public function test_handle_other_roles_throws_when_user_does_not_exist(): void
    {
        $this->request(['editor']);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->profile_edit->handle_other_roles(999999);
    }
}
