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
        unset($_REQUEST[$this->form], $_POST['_aikon_rm_other_roles_nonce']);
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
        $this->submit_profile_form($user_id, ['editor']);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('subscriber', $roles);
        $this->assertContains('editor', $roles);
    }

    public function test_can_add_multiple_roles_to_a_user(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->submit_profile_form($user_id, ['editor', 'author']);

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
        $this->submit_profile_form($user_id, ['author']);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('author', $roles);
        $this->assertNotContains('editor', $roles);
    }

    public function test_submitting_empty_array_removes_all_secondary_roles(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $user = new WP_User($user_id);
        $user->add_role('editor');

        $this->submit_profile_form($user_id, []);

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
        $this->submit_profile_form($user_id, ['made-up-role', 'editor']);

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

        $this->submit_profile_form($user_id, ['temp-role']);

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
        $this->submit_profile_form($user_id, ['administrator']);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertNotContains('administrator', $roles);
        $this->assertCount(1, $roles);
    }

    // =========================================================================
    // Error handling
    // =========================================================================

    public function test_handle_other_roles_does_nothing_when_user_does_not_exist(): void
    {
        $this->request(['editor']);
        $_POST['_aikon_rm_other_roles_nonce'] = wp_create_nonce('aikon_role_manager_other_roles_999999');

        // Must not throw: profile_update runs mid-request and an exception would be fatal
        $this->profile_edit->handle_other_roles(999999);
        unset($_POST['_aikon_rm_other_roles_nonce']);

        $this->assertFalse(get_userdata(999999));
    }

    // =========================================================================
    // Privilege escalation and integrity
    // =========================================================================

    /**
     * A delegated user manager: can promote and edit users, but is not an admin.
     */
    private function create_user_manager(): int
    {
        $this->manager->add_role('user-manager', 'User Manager', [
            'read'          => true,
            'list_users'    => true,
            'edit_users'    => true,
            'promote_users' => true,
        ]);

        return self::factory()->user->create(['role' => 'user-manager']);
    }

    /**
     * Run the handler the way WordPress does after a profile form save.
     *
     * @param string[] $roles
     */
    private function submit_profile_form(int $user_id, array $roles): void
    {
        $old_user = $this->fresh_user($user_id);

        $this->request($roles);
        $_POST['_aikon_rm_other_roles_nonce'] = wp_create_nonce('aikon_role_manager_other_roles_' . $user_id);

        $this->profile_edit->handle_other_roles($user_id, $old_user);

        unset($_POST['_aikon_rm_other_roles_nonce']);
    }

    public function test_user_manager_cannot_give_themselves_administrator(): void
    {
        $manager_id = $this->create_user_manager();
        wp_set_current_user($manager_id);

        $this->submit_profile_form($manager_id, ['administrator']);

        $this->assertNotContains('administrator', $this->fresh_user($manager_id)->roles);
    }

    public function test_user_can_add_own_role_that_grants_nothing_new(): void
    {
        $manager_id = $this->create_user_manager();
        wp_set_current_user($manager_id);

        $this->submit_profile_form($manager_id, ['subscriber']);

        $this->assertContains('subscriber', $this->fresh_user($manager_id)->roles);
    }

    public function test_roles_excluded_by_editable_roles_cannot_be_assigned(): void
    {
        $manager_id = $this->create_user_manager();
        wp_set_current_user($manager_id);

        add_filter('editable_roles', function (array $roles): array {
            unset($roles['administrator']);
            return $roles;
        });

        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $this->submit_profile_form($user_id, ['administrator', 'author']);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertNotContains('administrator', $roles);
        $this->assertContains('author', $roles);
    }

    public function test_roles_excluded_by_editable_roles_are_not_removed(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        (new WP_User($user_id))->add_role('administrator');

        $manager_id = $this->create_user_manager();
        wp_set_current_user($manager_id);
        add_filter('editable_roles', function (array $roles): array {
            unset($roles['administrator']);
            return $roles;
        });

        // The administrator checkbox is not submitted (disabled in the form)
        $this->submit_profile_form($user_id, []);

        $this->assertContains('administrator', $this->fresh_user($user_id)->roles);
    }

    public function test_secondary_roles_kept_when_profile_updated_without_the_form(): void
    {
        // e.g. wp_update_user() from the REST API or another plugin
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        (new WP_User($user_id))->add_role('editor');

        wp_update_user(['ID' => $user_id, 'display_name' => 'Renamed']);

        $this->assertContains('editor', $this->fresh_user($user_id)->roles);
    }

    public function test_submission_without_valid_nonce_is_ignored(): void
    {
        $user_id = self::factory()->user->create(['role' => 'subscriber']);

        $this->request(['editor']);
        $_POST['_aikon_rm_other_roles_nonce'] = 'forged';
        $this->profile_edit->handle_other_roles($user_id, $this->fresh_user($user_id));
        unset($_POST['_aikon_rm_other_roles_nonce']);

        $this->assertNotContains('editor', $this->fresh_user($user_id)->roles);
    }

    public function test_secondary_roles_survive_core_resetting_the_primary_role(): void
    {
        // On the user-edit screen core calls set_role() with the primary role,
        // which drops all other roles before profile_update fires.
        $user_id = self::factory()->user->create(['role' => 'subscriber']);
        $user    = new WP_User($user_id);
        $user->add_role('editor');
        $user->add_role('author');

        $old_user = $this->fresh_user($user_id);
        $this->fresh_user($user_id)->set_role('subscriber');

        $this->request(['editor', 'author']);
        $_POST['_aikon_rm_other_roles_nonce'] = wp_create_nonce('aikon_role_manager_other_roles_' . $user_id);
        $this->profile_edit->handle_other_roles($user_id, $old_user);
        unset($_POST['_aikon_rm_other_roles_nonce']);

        $roles = $this->fresh_user($user_id)->roles;
        $this->assertContains('editor', $roles);
        $this->assertContains('author', $roles);
    }
}
