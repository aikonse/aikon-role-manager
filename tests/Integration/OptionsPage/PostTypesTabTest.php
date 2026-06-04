<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\OptionsPage\Tabs\PostTypesTab;
use WP_UnitTestCase;

/**
 * Integration tests for PostTypesTab.
 *
 * This tab is view-only — handle() is a no-op. Tests verify that
 * get_post_types_capabilities() returns correct data for the registered
 * WordPress post types.
 */
class PostTypesTabTest extends WP_UnitTestCase
{
    private PostTypesTab $tab;

    public function set_up(): void
    {
        parent::set_up();
        $this->tab = new PostTypesTab();
    }

    // =========================================================================
    // Return structure
    // =========================================================================

    public function test_returns_an_array(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        $this->assertIsArray($result);
    }

    public function test_each_entry_has_label_and_capabilities_keys(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        foreach ($result as $post_type => $data) {
            $this->assertArrayHasKey('label', $data, "Missing 'label' for post type '$post_type'");
            $this->assertArrayHasKey('capabilities', $data, "Missing 'capabilities' for post type '$post_type'");
        }
    }

    public function test_capabilities_for_each_post_type_is_a_non_empty_array(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        foreach ($result as $post_type => $data) {
            $this->assertIsArray($data['capabilities'], "capabilities for '$post_type' should be an array");
            $this->assertNotEmpty($data['capabilities'], "capabilities for '$post_type' should not be empty");
        }
    }

    // =========================================================================
    // Built-in WordPress post types
    // =========================================================================

    public function test_includes_the_post_post_type(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        $this->assertArrayHasKey('post', $result);
    }

    public function test_includes_the_page_post_type(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        $this->assertArrayHasKey('page', $result);
    }

    public function test_post_type_label_is_a_non_empty_string(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        foreach ($result as $post_type => $data) {
            $this->assertIsString($data['label'], "label for '$post_type' should be a string");
            $this->assertNotEmpty($data['label'], "label for '$post_type' should not be empty");
        }
    }

    public function test_post_type_capabilities_include_standard_capability_keys(): void
    {
        $result = $this->tab->get_post_types_capabilities();

        // Every WP post type exposes at least these primitive capability keys
        $expected_keys = ['edit_posts', 'delete_posts', 'publish_posts'];

        foreach ($expected_keys as $cap) {
            $this->assertArrayHasKey(
                $cap,
                $result['post']['capabilities'],
                "Expected capability key '$cap' for the 'post' post type"
            );
        }
    }

    // =========================================================================
    // Custom post types
    // =========================================================================

    public function test_includes_custom_post_type_when_registered_with_show_ui(): void
    {
        register_post_type('aikon_test_cpt', [
            'label'    => 'Test CPT',
            'show_ui'  => true,
            'public'   => true,
        ]);

        $result = $this->tab->get_post_types_capabilities();

        $this->assertArrayHasKey('aikon_test_cpt', $result);

        unregister_post_type('aikon_test_cpt');
    }

    public function test_excludes_post_type_registered_without_show_ui(): void
    {
        register_post_type('aikon_hidden_cpt', [
            'label'   => 'Hidden CPT',
            'show_ui' => false,
            'public'  => false,
        ]);

        $result = $this->tab->get_post_types_capabilities();

        $this->assertArrayNotHasKey('aikon_hidden_cpt', $result);

        unregister_post_type('aikon_hidden_cpt');
    }
}
