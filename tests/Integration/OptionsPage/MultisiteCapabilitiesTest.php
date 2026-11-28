<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\Manager\SettingsManager;

use function Aikon\RoleManager\nonce_action;

use Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab;
use WP_Roles;

use WP_UnitTestCase;

/**
 * On multisite, site administrators who are not super admins must not be able
 * to grant themselves network-level or super-admin-only capabilities.
 *
 * Run with: WP_MULTISITE=1 vendor/bin/phpunit -c phpunit.integration.xml --group ms-required
 *
 * @group ms-required
 */
class MultisiteCapabilitiesTest extends WP_UnitTestCase
{
    private RoleManager $manager;
    private int $site_admin_id;

    public function set_up(): void
    {
        parent::set_up();

        if (!is_multisite()) {
            $this->markTestSkipped('Requires multisite: run with WP_MULTISITE=1');
        }

        global $wp_roles;
        $wp_roles = new WP_Roles();

        RoleManager::$instance     = null;
        SettingsManager::$instance = null;
        delete_option(SettingsManager::OPTION_KEY);

        $this->manager = RoleManager::getInstance();
        $this->manager->add_role('custom-role', 'Custom Role', ['read' => true]);

        // A site administrator, not a super admin
        $this->site_admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($this->site_admin_id);
    }

    public function tear_down(): void
    {
        foreach (['action', '_wpnonce', 'role', 'role_caps'] as $key) {
            unset($_POST[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tear_down();
    }

    /**
     * @param array<string, string> $caps
     */
    private function save_capabilities(string $role, array $caps): CapabilitiesTab
    {
        $data = [
            'action'    => 'save_capabilities',
            'role'      => $role,
            'role_caps' => $caps,
            '_wpnonce'  => wp_create_nonce(nonce_action('save_capabilities')),
        ];

        $_SERVER['REQUEST_METHOD'] = 'POST';
        foreach ($data as $key => $value) {
            $_POST[$key]    = $value;
            $_REQUEST[$key] = $value;
        }

        $tab = new CapabilitiesTab();
        $tab->handle();

        return $tab;
    }

    /**
     * @return array<string, bool>
     */
    private function role_caps(string $role): array
    {
        return $this->manager->current_roles()[$role]['capabilities'];
    }

    public function test_site_admin_is_not_super_admin(): void
    {
        $this->assertTrue(is_multisite());
        $this->assertFalse(is_super_admin($this->site_admin_id));
    }

    public function test_site_admin_cannot_grant_network_capabilities(): void
    {
        $this->save_capabilities('custom-role', [
            'read'                 => '1',
            'edit_posts'           => '1',
            'manage_network'       => '1',
            'manage_network_users' => '1',
        ]);

        $caps = $this->role_caps('custom-role');

        $this->assertTrue($caps['edit_posts'] ?? false, 'Ordinary capabilities are still saved');
        $this->assertArrayNotHasKey('manage_network', $caps);
        $this->assertArrayNotHasKey('manage_network_users', $caps);
    }

    public function test_site_admin_cannot_grant_super_admin_only_capabilities(): void
    {
        $this->save_capabilities('custom-role', [
            'read'            => '1',
            'unfiltered_html' => '1',
            'install_plugins' => '1',
        ]);

        $caps = $this->role_caps('custom-role');

        $this->assertArrayNotHasKey('unfiltered_html', $caps);
        $this->assertArrayNotHasKey('install_plugins', $caps);
    }

    public function test_site_admin_cannot_escalate_own_role_to_network_admin(): void
    {
        // The site admin removes protection and edits their own role
        SettingsManager::getInstance()->set_protected_roles([]);

        $caps = array_map(fn ($v) => $v ? '1' : '0', $this->role_caps('administrator'));
        $caps['manage_network']       = '1';
        $caps['manage_network_users'] = '1';

        $this->save_capabilities('administrator', $caps);

        // Re-read roles from the database and the user's capabilities
        global $wp_roles;
        $wp_roles = new WP_Roles();
        $user     = new \WP_User($this->site_admin_id);

        $this->assertFalse($user->has_cap('manage_network'));
        $this->assertFalse($user->has_cap('manage_network_users'));
    }

    public function test_site_admin_cannot_revoke_existing_network_capabilities(): void
    {
        // Granted by a super admin earlier
        $this->manager->update_role_capabilities('custom-role', [
            'read'                 => true,
            'manage_network_users' => true,
        ]);

        $this->save_capabilities('custom-role', [
            'read'                 => '1',
            'manage_network_users' => '0',
        ]);
        $this->assertTrue($this->role_caps('custom-role')['manage_network_users'] ?? false);

        // Leaving it out of the submission (as the disabled checkbox does) keeps it too
        $this->save_capabilities('custom-role', ['read' => '1']);
        $this->assertTrue($this->role_caps('custom-role')['manage_network_users'] ?? false);
    }

    public function test_rejected_changes_produce_a_warning(): void
    {
        $tab = $this->save_capabilities('custom-role', [
            'read'           => '1',
            'manage_network' => '1',
        ]);

        $this->assertArrayHasKey('restricted_capabilities', (array) $tab->errors());
    }

    public function test_super_admin_can_grant_network_capabilities(): void
    {
        grant_super_admin($this->site_admin_id);

        $this->save_capabilities('custom-role', [
            'read'                 => '1',
            'manage_network_users' => '1',
        ]);

        $this->assertTrue($this->role_caps('custom-role')['manage_network_users'] ?? false);

        revoke_super_admin($this->site_admin_id);
    }

    public function test_restricted_capabilities_include_network_and_super_admin_only_caps(): void
    {
        $restricted = $this->manager->restricted_capabilities();

        foreach (['manage_network', 'manage_network_users', 'manage_sites', 'unfiltered_html', 'install_plugins'] as $cap) {
            $this->assertContains($cap, $restricted);
        }
        $this->assertNotContains('edit_posts', $restricted);
    }

    public function test_restricted_capabilities_empty_for_super_admin(): void
    {
        grant_super_admin($this->site_admin_id);

        $this->assertSame([], $this->manager->restricted_capabilities());

        revoke_super_admin($this->site_admin_id);
    }

    public function test_restricted_capabilities_are_locked_in_the_form(): void
    {
        $this->manager->update_role_capabilities('custom-role', [
            'read'                 => true,
            'manage_network_users' => true,
        ]);

        $_GET['role']     = 'custom-role';
        $_REQUEST['role'] = 'custom-role';

        ob_start();
        (new CapabilitiesTab())->render();
        $html = (string) ob_get_clean();

        unset($_GET['role']);

        // The role's own form renders checkboxes as value="1" name="..." (the
        // "add capabilities" list renders name first), which tells them apart.

        // Restricted: disabled checkbox, no hidden "0" input that would revoke it
        $this->assertMatchesRegularExpression('/<input type="checkbox" value="1" name="role_caps\[manage_network_users\]"[^>]*disabled/', $html);
        $this->assertDoesNotMatchRegularExpression('/type="hidden" name="role_caps\[manage_network_users\]"/', $html);

        // Not restricted: still editable
        $this->assertMatchesRegularExpression('/type="hidden" name="role_caps\[read\]"/', $html);
        $this->assertMatchesRegularExpression('/<input type="checkbox" value="1" name="role_caps\[read\]"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<input type="checkbox" value="1" name="role_caps\[read\]"[^>]*disabled/', $html);

        // Restricted caps cannot be added from the list either
        $this->assertMatchesRegularExpression('/name="role_caps\[manage_sites\]"\s+disabled/', $html);
    }
}
