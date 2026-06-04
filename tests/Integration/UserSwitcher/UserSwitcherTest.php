<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\UserSwitcher;

use Aikon\RoleManager\UserSwitcher\UserSwitcher;
use WP_Admin_Bar;
use WP_UnitTestCase;
use WP_User;

/**
 * Integration tests for UserSwitcher.
 *
 * handle_switch() and handle_switch_back() success paths both call
 * wp_safe_redirect() + exit, which would terminate the test process.
 * Those paths are therefore not tested here. All other branches —
 * row-action visibility, admin-bar indicator, cookie validation, and
 * rejection/wp_die paths — are covered below.
 *
 * wp_die() is overridden by the WP test suite to throw WPDieException,
 * so rejection paths can be asserted with expectException.
 */
class UserSwitcherTest extends WP_UnitTestCase
{
    private UserSwitcher $switcher;
    private int $admin_id;
    private int $editor_id;

    private const COOKIE_NAME  = 'aikon_role_manager_user_switcher';
    private const ARG_ACTION   = 'aikon_role_manager_switch_action';
    private const ARG_USER     = 'aikon_role_manager_switch_user';

    public function set_up(): void
    {
        parent::set_up();

        // WP_Admin_Bar is not loaded by the test bootstrap (CLI, not a web request)
        require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';

        $this->switcher  = new UserSwitcher();
        $this->admin_id  = self::factory()->user->create(['role' => 'administrator']);
        $this->editor_id = self::factory()->user->create(['role' => 'editor']);

        wp_set_current_user($this->admin_id);
    }

    public function tear_down(): void
    {
        unset($_COOKIE[self::COOKIE_NAME]);
        unset($_GET[self::ARG_ACTION], $_GET[self::ARG_USER], $_GET['_wpnonce']);
        parent::tear_down();
    }

    // -------------------------------------------------------------------------
    // Cookie helper — replicates the signing logic of set_switcher_cookie()
    // -------------------------------------------------------------------------

    private function set_valid_switcher_cookie(int $original_user_id): void
    {
        $_COOKIE[self::COOKIE_NAME] = $original_user_id . '|' . wp_hash($original_user_id . \AUTH_SALT);
    }

    // =========================================================================
    // Row action: "Login as"
    // =========================================================================

    public function test_login_as_action_shown_for_admin_viewing_another_user(): void
    {
        $editor = get_userdata($this->editor_id);
        $actions = $this->switcher->add_switch_action([], $editor);

        $this->assertArrayHasKey('aikon_role_manager_switch', $actions);
    }

    public function test_login_as_action_contains_switch_url_with_nonce(): void
    {
        $editor  = get_userdata($this->editor_id);
        $actions = $this->switcher->add_switch_action([], $editor);
        $html    = $actions['aikon_role_manager_switch'];

        $this->assertStringContainsString(self::ARG_ACTION . '=switch', $html);
        $this->assertStringContainsString(self::ARG_USER . '=' . $this->editor_id, $html);
        $this->assertStringContainsString('_wpnonce=', $html);
    }

    public function test_login_as_action_preserves_existing_row_actions(): void
    {
        $editor  = get_userdata($this->editor_id);
        $actions = $this->switcher->add_switch_action(['edit' => '<a>Edit</a>'], $editor);

        $this->assertArrayHasKey('edit', $actions);
        $this->assertArrayHasKey('aikon_role_manager_switch', $actions);
    }

    public function test_login_as_action_hidden_when_current_user_lacks_edit_users(): void
    {
        wp_set_current_user($this->editor_id);
        $subscriber_id = self::factory()->user->create(['role' => 'subscriber']);

        $actions = $this->switcher->add_switch_action([], get_userdata($subscriber_id));

        $this->assertArrayNotHasKey('aikon_role_manager_switch', $actions);
    }

    public function test_login_as_action_hidden_when_viewing_own_user_row(): void
    {
        $admin   = get_userdata($this->admin_id);
        $actions = $this->switcher->add_switch_action([], $admin);

        $this->assertArrayNotHasKey('aikon_role_manager_switch', $actions);
    }

    public function test_login_as_action_hidden_when_already_switched(): void
    {
        // Simulate the admin having switched to the editor
        $this->set_valid_switcher_cookie($this->admin_id);
        wp_set_current_user($this->editor_id);

        $subscriber_id = self::factory()->user->create(['role' => 'subscriber']);
        $actions = $this->switcher->add_switch_action([], get_userdata($subscriber_id));

        $this->assertArrayNotHasKey('aikon_role_manager_switch', $actions);
    }

    // =========================================================================
    // Admin bar: indicator and switch-back node
    // =========================================================================

    public function test_admin_bar_item_absent_when_not_switched(): void
    {
        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $this->assertNull($bar->get_node('aikon-role-manager-user-switcher'));
    }

    public function test_admin_bar_indicator_present_when_switched(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        wp_set_current_user($this->editor_id);

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $this->assertNotNull($bar->get_node('aikon-role-manager-user-switcher'));
    }

    public function test_admin_bar_indicator_shows_impersonated_user_name(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        $editor = get_userdata($this->editor_id);
        wp_set_current_user($this->editor_id);

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $node = $bar->get_node('aikon-role-manager-user-switcher');
        $this->assertStringContainsString($editor->display_name, $node->title);
    }

    public function test_switch_back_node_present_when_switched(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        wp_set_current_user($this->editor_id);

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $this->assertNotNull($bar->get_node('aikon-role-manager-switch-back'));
    }

    public function test_switch_back_node_title_contains_original_user_name(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        $admin = get_userdata($this->admin_id);
        wp_set_current_user($this->editor_id);

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $node = $bar->get_node('aikon-role-manager-switch-back');
        $this->assertStringContainsString($admin->display_name, $node->title);
    }

    public function test_switch_back_node_href_contains_switch_back_action(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        wp_set_current_user($this->editor_id);

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $node = $bar->get_node('aikon-role-manager-switch-back');
        $this->assertStringContainsString(self::ARG_ACTION . '=switch_back', $node->href);
        $this->assertStringContainsString('_wpnonce=', $node->href);
    }

    // =========================================================================
    // Cookie validation
    // =========================================================================

    public function test_admin_bar_item_absent_with_tampered_cookie(): void
    {
        $_COOKIE[self::COOKIE_NAME] = $this->admin_id . '|tampered_hash_value';

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $this->assertNull($bar->get_node('aikon-role-manager-user-switcher'));
    }

    public function test_admin_bar_item_absent_with_malformed_cookie(): void
    {
        $_COOKIE[self::COOKIE_NAME] = 'not-a-valid-cookie-value';

        $bar = new WP_Admin_Bar();
        $this->switcher->add_admin_bar_item($bar);

        $this->assertNull($bar->get_node('aikon-role-manager-user-switcher'));
    }

    public function test_login_as_action_shown_even_with_tampered_cookie(): void
    {
        // A tampered cookie fails the signature check → is_switched() returns false
        // → the "already switched" guard does NOT block the action
        $_COOKIE[self::COOKIE_NAME] = $this->admin_id . '|tampered_hash';

        $editor  = get_userdata($this->editor_id);
        $actions = $this->switcher->add_switch_action([], $editor);

        $this->assertArrayHasKey('aikon_role_manager_switch', $actions);
    }

    // =========================================================================
    // Switch request handler — rejection paths (wp_die → WPDieException)
    // =========================================================================

    public function test_handle_switch_request_is_noop_without_action_param(): void
    {
        // No GET params — should return silently without throwing
        $this->switcher->handle_switch_request();
        $this->addToAssertionCount(1);
    }

    public function test_handle_switch_dies_when_user_lacks_edit_users(): void
    {
        wp_set_current_user($this->editor_id);

        $_GET[self::ARG_ACTION] = 'switch';
        $_GET[self::ARG_USER]   = (string) $this->admin_id;

        $this->expectException(\WPDieException::class);
        $this->expectExceptionMessage('You do not have permission to switch users.');

        $this->switcher->handle_switch_request();
    }

    public function test_handle_switch_dies_with_invalid_nonce(): void
    {
        $_GET[self::ARG_ACTION] = 'switch';
        $_GET[self::ARG_USER]   = (string) $this->editor_id;
        $_GET['_wpnonce']       = 'invalid_nonce';

        $this->expectException(\WPDieException::class);
        $this->expectExceptionMessage('Security check failed.');

        $this->switcher->handle_switch_request();
    }

    public function test_handle_switch_back_dies_without_active_session(): void
    {
        $_GET[self::ARG_ACTION] = 'switch_back';

        $this->expectException(\WPDieException::class);
        $this->expectExceptionMessage('No active user switch session found.');

        $this->switcher->handle_switch_request();
    }

    public function test_handle_switch_back_dies_with_invalid_nonce(): void
    {
        $this->set_valid_switcher_cookie($this->admin_id);
        wp_set_current_user($this->editor_id);

        $_GET[self::ARG_ACTION] = 'switch_back';
        $_GET['_wpnonce']       = 'invalid_nonce';

        $this->expectException(\WPDieException::class);
        $this->expectExceptionMessage('Security check failed.');

        $this->switcher->handle_switch_request();
    }
}
