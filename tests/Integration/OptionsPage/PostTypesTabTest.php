<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Integration\OptionsPage;

use Aikon\RoleManager\Manager\PostTypeManager;
use Aikon\RoleManager\OptionsPage\Tabs\PostTypesTab;
use WP_UnitTestCase;

/**
 * Integration tests for PostTypesTab.
 *
 * get_post_types_capabilities() is a read-only query — tested directly.
 * handle() success paths (save/remove) call wp_redirect() + exit, so those
 * are exercised via PostTypeManager directly. Validation failures are tested
 * through PostTypesTab::handle() with a simulated POST, since those return
 * early without redirecting.
 */
class PostTypesTabTest extends WP_UnitTestCase
{
    private PostTypesTab $tab;

    public function set_up(): void
    {
        parent::set_up();

        PostTypeManager::$instance = null;
        delete_option(PostTypeManager::OPTION_KEY);

        $admin_id = self::factory()->user->create(['role' => 'administrator']);
        wp_set_current_user($admin_id);

        $this->tab = new PostTypesTab();
    }

    public function tear_down(): void
    {
        PostTypeManager::$instance = null;
        delete_option(PostTypeManager::OPTION_KEY);

        foreach (['action', 'post_type', 'capability_type'] as $key) {
            unset($_POST[$key], $_GET[$key], $_REQUEST[$key]);
        }
        $_SERVER['REQUEST_METHOD'] = 'GET';

        parent::tear_down();
    }

    /**
     * @param array<string,string> $data
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

    // =========================================================================
    // Save override — validation failures (tested via handle())
    // =========================================================================

    public function test_save_override_form_rejected_when_capability_type_is_empty(): void
    {
        $this->post([
            'action'          => 'save_post_type_override',
            'post_type'       => 'post',
            'capability_type' => '',
        ]);

        $this->tab->handle();

        $this->assertArrayHasKey('capability_type', $this->tab->errors());
        $this->assertFalse(PostTypeManager::getInstance()->has_override('post'));
    }

    public function test_save_override_form_rejected_when_post_type_does_not_exist(): void
    {
        $this->post([
            'action'          => 'save_post_type_override',
            'post_type'       => 'nonexistent_pt',
            'capability_type' => 'product',
        ]);

        $this->tab->handle();

        // Invalid post type is reported via notice only — no field error, nothing saved
        $this->assertEmpty($this->tab->errors());
        $this->assertFalse(PostTypeManager::getInstance()->has_override('nonexistent_pt'));
    }

    // =========================================================================
    // Save override — success path (tested via PostTypeManager directly)
    // =========================================================================

    public function test_save_override_persists_capability_type_for_post_type(): void
    {
        PostTypeManager::getInstance()->set_override('post', 'article');

        $this->assertTrue(PostTypeManager::getInstance()->has_override('post'));
        $this->assertSame('article', PostTypeManager::getInstance()->get_overrides()['post']);
    }

    public function test_save_override_can_be_updated(): void
    {
        PostTypeManager::getInstance()->set_override('post', 'article');
        PostTypeManager::getInstance()->set_override('post', 'entry');

        $this->assertSame('entry', PostTypeManager::getInstance()->get_overrides()['post']);
    }

    // =========================================================================
    // Remove override — validation failure (tested via handle())
    // =========================================================================

    public function test_remove_override_does_nothing_when_post_type_is_invalid(): void
    {
        PostTypeManager::getInstance()->set_override('post', 'article');

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['action']            = 'remove_post_type_override';
        $_GET['post_type']         = 'nonexistent_pt';
        $_REQUEST['action']        = 'remove_post_type_override';
        $_REQUEST['post_type']     = 'nonexistent_pt';

        $this->tab->handle();

        // Override for 'post' is untouched
        $this->assertTrue(PostTypeManager::getInstance()->has_override('post'));
    }

    // =========================================================================
    // Remove override — success path (tested via PostTypeManager directly)
    // =========================================================================

    public function test_remove_override_clears_the_saved_override(): void
    {
        PostTypeManager::getInstance()->set_override('post', 'article');
        PostTypeManager::getInstance()->remove_override('post');

        $this->assertFalse(PostTypeManager::getInstance()->has_override('post'));
    }

    public function test_remove_override_does_not_affect_other_overrides(): void
    {
        PostTypeManager::getInstance()->set_override('post', 'article');
        PostTypeManager::getInstance()->set_override('page', 'document');

        PostTypeManager::getInstance()->remove_override('post');

        $this->assertSame('document', PostTypeManager::getInstance()->get_overrides()['page']);
    }
}
