<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Unit\OptionsPage;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\Manager\SettingsManager;
use Aikon\RoleManager\OptionsPage\Tabs\CapabilitiesTab;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CapabilitiesTab::handle_save_capabilities validation.
 *
 * Tests that can be verified without a real WordPress environment:
 * role-not-found, empty capabilities, and invalid capability key.
 *
 * Cases that depend on actual WordPress sanitization behaviour live in the
 * Integration suite instead.
 */
class CapabilitiesTabTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('__')->returnArg(1);

        global $wp_roles;
        $wp_roles = new \WP_Roles();
        $wp_roles->roles = [
            'administrator' => ['name' => 'Administrator', 'capabilities' => []],
            'custom-role'   => ['name' => 'Custom Role',   'capabilities' => ['read' => true]],
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

        foreach (['action', 'role', 'role_caps'] as $key) {
            unset($_POST[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        Monkey\tearDown();
        parent::tearDown();
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

    private function stubWpFunctions(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('sanitize_key')->returnArg(1);
        // SettingsManager::get_settings() calls get_option(); returning false
        // triggers the defaults (administrator protected). 'custom-role' is not
        // administrator, so change_role() returns true and the test proceeds normally.
        Functions\when('get_option')->justReturn(false);
    }

    // =========================================================================
    // role existence check
    // =========================================================================

    public function test_save_capabilities_rejected_when_role_does_not_exist(): void
    {
        $this->stubWpFunctions();

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'nonexistent-role',
            'role_caps' => ['read' => '1'],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertArrayHasKey('role', $tab->errors());
    }

    // =========================================================================
    // empty capabilities
    // =========================================================================

    public function test_save_capabilities_rejected_when_capabilities_array_is_empty(): void
    {
        $this->stubWpFunctions();

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => [],
        ]);

        $tab = new CapabilitiesTab();
        $tab->handle();

        $this->assertArrayHasKey('capabilities', $tab->errors());
    }

    // =========================================================================
    // invalid capability key
    // =========================================================================

    public function test_save_capabilities_throws_when_capability_key_is_invalid(): void
    {
        Functions\when('sanitize_text_field')->returnArg(1);
        Functions\when('current_user_can')->justReturn(true);
        Functions\when('get_option')->justReturn(false);
        // sanitize_key returning 'a' (1 char) makes validate_capability() return null
        Functions\when('sanitize_key')->justReturn('a');

        $this->post([
            'action'    => 'save_capabilities',
            'role'      => 'custom-role',
            'role_caps' => ['bad-key' => '1'],
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid capability');

        $tab = new CapabilitiesTab();
        $tab->handle();
    }
}
