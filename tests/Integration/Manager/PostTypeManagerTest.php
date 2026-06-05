<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\Manager;

use Aikon\RoleManager\Manager\PostTypeManager;
use WP_UnitTestCase;

/**
 * Integration tests for PostTypeManager.
 *
 * Success paths for set/remove are tested directly on the manager — they hit
 * the real DB option. The register_post_type_args filter is verified by
 * registering a throw-away post type after apply_overrides() and inspecting
 * the resulting cap object.
 */
class PostTypeManagerTest extends WP_UnitTestCase
{
    private PostTypeManager $manager;

    public function set_up(): void
    {
        parent::set_up();

        PostTypeManager::$instance = null;
        delete_option(PostTypeManager::OPTION_KEY);

        $this->manager = PostTypeManager::getInstance();
    }

    public function tear_down(): void
    {
        PostTypeManager::$instance = null;
        delete_option(PostTypeManager::OPTION_KEY);

        // Clean up any filter registered by apply_overrides() so tests don't bleed
        remove_all_filters('register_post_type_args');

        foreach (['arm_test_pt', 'arm_explicit_pt', 'arm_noop_pt'] as $slug) {
            if (post_type_exists($slug)) {
                unregister_post_type($slug);
            }
        }

        parent::tear_down();
    }

    // =========================================================================
    // get_overrides
    // =========================================================================

    public function test_get_overrides_returns_empty_array_when_no_option_set(): void
    {
        $this->assertSame([], $this->manager->get_overrides());
    }

    // =========================================================================
    // set_override
    // =========================================================================

    public function test_set_override_persists_to_the_option(): void
    {
        $this->manager->set_override('post', 'article');

        $this->assertSame(['post' => 'article'], $this->manager->get_overrides());
    }

    public function test_set_override_can_update_an_existing_entry(): void
    {
        $this->manager->set_override('post', 'article');
        $this->manager->set_override('post', 'entry');

        $this->assertSame('entry', $this->manager->get_overrides()['post']);
    }

    public function test_set_override_does_not_affect_other_entries(): void
    {
        $this->manager->set_override('post', 'article');
        $this->manager->set_override('page', 'document');

        $overrides = $this->manager->get_overrides();
        $this->assertSame('article', $overrides['post']);
        $this->assertSame('document', $overrides['page']);
    }

    // =========================================================================
    // remove_override
    // =========================================================================

    public function test_remove_override_deletes_the_entry(): void
    {
        $this->manager->set_override('post', 'article');
        $this->manager->remove_override('post');

        $this->assertArrayNotHasKey('post', $this->manager->get_overrides());
    }

    public function test_remove_override_does_not_affect_other_entries(): void
    {
        $this->manager->set_override('post', 'article');
        $this->manager->set_override('page', 'document');

        $this->manager->remove_override('post');

        $this->assertSame('document', $this->manager->get_overrides()['page']);
    }

    public function test_remove_override_is_a_noop_for_nonexistent_entry(): void
    {
        $this->manager->set_override('page', 'document');
        $this->manager->remove_override('nonexistent');

        $this->assertSame(['page' => 'document'], $this->manager->get_overrides());
    }

    // =========================================================================
    // has_override
    // =========================================================================

    public function test_has_override_returns_true_when_override_is_set(): void
    {
        $this->manager->set_override('post', 'article');

        $this->assertTrue($this->manager->has_override('post'));
    }

    public function test_has_override_returns_false_when_no_override_is_set(): void
    {
        $this->assertFalse($this->manager->has_override('post'));
    }

    public function test_has_override_returns_false_after_override_is_removed(): void
    {
        $this->manager->set_override('post', 'article');
        $this->manager->remove_override('post');

        $this->assertFalse($this->manager->has_override('post'));
    }

    // =========================================================================
    // validate_capability_type
    // =========================================================================

    public function test_validate_capability_type_accepts_a_valid_slug(): void
    {
        $this->assertSame('product', $this->manager->validate_capability_type('product'));
    }

    public function test_validate_capability_type_returns_null_for_empty_string(): void
    {
        $this->assertNull($this->manager->validate_capability_type(''));
    }

    public function test_validate_capability_type_returns_null_for_whitespace_only_string(): void
    {
        $this->assertNull($this->manager->validate_capability_type('   '));
    }

    public function test_validate_capability_type_returns_null_for_non_string(): void
    {
        $this->assertNull($this->manager->validate_capability_type(42));
        $this->assertNull($this->manager->validate_capability_type(null));
        $this->assertNull($this->manager->validate_capability_type([]));
    }

    public function test_validate_capability_type_sanitizes_to_lowercase_key(): void
    {
        // sanitize_key lowercases and strips special chars
        $this->assertSame('mytype', $this->manager->validate_capability_type('MyType'));
    }

    // =========================================================================
    // apply_overrides — register_post_type_args filter
    // =========================================================================

    public function test_apply_overrides_changes_capability_type_for_registered_post_type(): void
    {
        $this->manager->set_override('arm_test_pt', 'product');
        $this->manager->apply_overrides();

        register_post_type('arm_test_pt', [
            'label'   => 'Test PT',
            'show_ui' => true,
            'public'  => true,
        ]);

        $post_type = get_post_type_object('arm_test_pt');
        $this->assertNotNull($post_type);
        // capability_type 'product' → plural 'products' → edit_posts maps to edit_products
        $this->assertSame('edit_products', $post_type->cap->edit_posts);
        $this->assertSame('delete_products', $post_type->cap->delete_posts);
        $this->assertSame('publish_products', $post_type->cap->publish_posts);
    }

    public function test_apply_overrides_clears_explicit_capabilities_so_they_are_regenerated(): void
    {
        $this->manager->set_override('arm_explicit_pt', 'item');
        $this->manager->apply_overrides();

        register_post_type('arm_explicit_pt', [
            'label'        => 'Explicit PT',
            'show_ui'      => true,
            'capabilities' => [
                'edit_posts' => 'manage_options', // hardcoded explicit cap — should be cleared
            ],
        ]);

        $post_type = get_post_type_object('arm_explicit_pt');
        $this->assertNotNull($post_type);
        // explicit 'manage_options' is gone; regenerated from capability_type 'item' → 'items'
        $this->assertSame('edit_items', $post_type->cap->edit_posts);
    }

    public function test_apply_overrides_does_not_affect_post_types_without_an_override(): void
    {
        $this->manager->set_override('arm_test_pt', 'product');
        $this->manager->apply_overrides();

        register_post_type('arm_noop_pt', [
            'label'   => 'No Override PT',
            'show_ui' => true,
            'public'  => true,
        ]);

        $post_type = get_post_type_object('arm_noop_pt');
        $this->assertNotNull($post_type);
        // No override → generated from default capability_type 'post'
        $this->assertSame('edit_posts', $post_type->cap->edit_posts);
    }
}
