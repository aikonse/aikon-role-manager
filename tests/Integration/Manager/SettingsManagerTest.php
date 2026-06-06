<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\Manager;

use Aikon\RoleManager\Manager\SettingsManager;
use WP_UnitTestCase;

/**
 * Integration tests for SettingsManager.
 *
 * All methods are exercised directly against the real DB option so we verify
 * that serialization round-trips correctly and that defaults are applied when
 * no option is stored.
 */
class SettingsManagerTest extends WP_UnitTestCase
{
    private SettingsManager $manager;

    public function set_up(): void
    {
        parent::set_up();

        SettingsManager::$instance = null;
        delete_option(SettingsManager::OPTION_KEY);

        $this->manager = SettingsManager::getInstance();
    }

    public function tear_down(): void
    {
        SettingsManager::$instance = null;
        delete_option(SettingsManager::OPTION_KEY);

        parent::tear_down();
    }

    // =========================================================================
    // Defaults (no option stored)
    // =========================================================================

    public function test_get_protected_roles_returns_administrator_by_default(): void
    {
        $this->assertSame(['administrator'], $this->manager->get_protected_roles());
    }

    public function test_get_protected_post_types_returns_empty_array_by_default(): void
    {
        $this->assertSame([], $this->manager->get_protected_post_types());
    }

    public function test_change_role_returns_false_for_administrator_by_default(): void
    {
        $this->assertFalse($this->manager->change_role('administrator'));
    }

    public function test_change_post_type_returns_true_for_any_post_type_by_default(): void
    {
        $this->assertTrue($this->manager->change_post_type('post'));
        $this->assertTrue($this->manager->change_post_type('page'));
    }

    // =========================================================================
    // set_protected_roles
    // =========================================================================

    public function test_set_protected_roles_persists_to_the_option(): void
    {
        $this->manager->set_protected_roles(['editor', 'author']);

        $this->assertSame(['editor', 'author'], $this->manager->get_protected_roles());
    }

    public function test_set_protected_roles_overwrites_the_previous_list(): void
    {
        $this->manager->set_protected_roles(['editor']);
        $this->manager->set_protected_roles(['author']);

        $this->assertSame(['author'], $this->manager->get_protected_roles());
    }

    public function test_set_protected_roles_can_clear_all_protected_roles(): void
    {
        $this->manager->set_protected_roles([]);

        $this->assertSame([], $this->manager->get_protected_roles());
    }

    public function test_set_protected_roles_sanitizes_values_with_sanitize_key(): void
    {
        // sanitize_key lowercases and strips anything that isn't [a-z0-9_-].
        // Spaces are stripped (not converted to hyphens), so 'My Role!' → 'myrole'.
        $this->manager->set_protected_roles(['My Role!', 'valid-role']);

        $protected = $this->manager->get_protected_roles();
        $this->assertContains('myrole', $protected);
        $this->assertContains('valid-role', $protected);
    }

    public function test_set_protected_roles_removes_entries_that_sanitize_to_empty(): void
    {
        // '!!!!' sanitizes to '' and should be filtered out
        $this->manager->set_protected_roles(['!!!!', 'valid']);

        $protected = $this->manager->get_protected_roles();
        $this->assertNotContains('', $protected);
        $this->assertContains('valid', $protected);
    }

    public function test_set_protected_roles_does_not_affect_protected_post_types(): void
    {
        $this->manager->set_protected_post_types(['post']);
        $this->manager->set_protected_roles(['editor']);

        $this->assertSame(['post'], $this->manager->get_protected_post_types());
    }

    // =========================================================================
    // set_protected_post_types
    // =========================================================================

    public function test_set_protected_post_types_persists_to_the_option(): void
    {
        $this->manager->set_protected_post_types(['post', 'page']);

        $this->assertSame(['post', 'page'], $this->manager->get_protected_post_types());
    }

    public function test_set_protected_post_types_overwrites_the_previous_list(): void
    {
        $this->manager->set_protected_post_types(['post']);
        $this->manager->set_protected_post_types(['page']);

        $this->assertSame(['page'], $this->manager->get_protected_post_types());
    }

    public function test_set_protected_post_types_can_clear_all(): void
    {
        $this->manager->set_protected_post_types(['post']);
        $this->manager->set_protected_post_types([]);

        $this->assertSame([], $this->manager->get_protected_post_types());
    }

    public function test_set_protected_post_types_does_not_affect_protected_roles(): void
    {
        $this->manager->set_protected_roles(['editor']);
        $this->manager->set_protected_post_types(['post']);

        $this->assertSame(['editor'], $this->manager->get_protected_roles());
    }

    // =========================================================================
    // change_role
    // =========================================================================

    public function test_change_role_returns_false_for_a_protected_role(): void
    {
        $this->manager->set_protected_roles(['administrator', 'editor']);

        $this->assertFalse($this->manager->change_role('administrator'));
        $this->assertFalse($this->manager->change_role('editor'));
    }

    public function test_change_role_returns_true_for_an_unprotected_role(): void
    {
        $this->manager->set_protected_roles(['administrator']);

        $this->assertTrue($this->manager->change_role('editor'));
        $this->assertTrue($this->manager->change_role('subscriber'));
    }

    public function test_change_role_returns_true_when_protected_list_is_empty(): void
    {
        $this->manager->set_protected_roles([]);

        $this->assertTrue($this->manager->change_role('administrator'));
    }

    // =========================================================================
    // change_post_type
    // =========================================================================

    public function test_change_post_type_returns_false_for_a_protected_post_type(): void
    {
        $this->manager->set_protected_post_types(['post', 'page']);

        $this->assertFalse($this->manager->change_post_type('post'));
        $this->assertFalse($this->manager->change_post_type('page'));
    }

    public function test_change_post_type_returns_true_for_an_unprotected_post_type(): void
    {
        $this->manager->set_protected_post_types(['post']);

        $this->assertTrue($this->manager->change_post_type('page'));
    }

    public function test_change_post_type_returns_true_when_protected_list_is_empty(): void
    {
        $this->manager->set_protected_post_types([]);

        $this->assertTrue($this->manager->change_post_type('post'));
    }

    // =========================================================================
    // Option survival across instance resets
    // =========================================================================

    public function test_settings_survive_instance_reset(): void
    {
        $this->manager->set_protected_roles(['editor']);
        $this->manager->set_protected_post_types(['post']);

        // Simulate a new request by resetting the singleton
        SettingsManager::$instance = null;
        $fresh = SettingsManager::getInstance();

        $this->assertSame(['editor'], $fresh->get_protected_roles());
        $this->assertSame(['post'], $fresh->get_protected_post_types());
    }
}
