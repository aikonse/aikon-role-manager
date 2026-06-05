<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use function Aikon\RoleManager\config;

use Aikon\RoleManager\Manager\PostTypeManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;

use Aikon\RoleManager\OptionsPage\Traits\HandlesActions;
use Aikon\RoleManager\OptionsPage\Traits\HandlesNotice;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;
use Aikon\RoleManager\Request;

use function Aikon\RoleManager\template;
use function Aikon\RoleManager\url_parser;

class PostTypesTab implements TabInterface
{
    use HasTitleAnSlug;
    use HandlesNotice;
    use HandlesActions;

    private PostTypeManager $manager;

    public function __construct()
    {
        $this->title   = __('Post Types Capabilities', 'aikon-role-manager');
        $this->slug    = 'post-types';
        $this->icon    = 'dashicons-media-document';
        $this->manager = PostTypeManager::getInstance();
    }

    public function handle(): void
    {
        $this->middleware(function (Request $request): Request {
            if (!current_user_can('manage_options')) {
                throw new \Exception('You do not have permission to manage post type capabilities');
            }
            return $request;
        });

        $this->post_action('action', 'save_post_type_override', [$this, 'handle_save_override']);
        $this->get_action('action', 'remove_post_type_override', [$this, 'handle_remove_override']);
    }

    /**
     * @param Request $request
     * @return void
     */
    public function handle_save_override(Request $request): void
    {
        $request->validate([
            'capability_type' => 'string|minlength:1',
            'post_type'       => 'string|minlength:1',
        ]);

        $post_type = sanitize_key($request->string('post_type'));
        $capability_type = $this->manager->validate_capability_type($request->string('capability_type'));

        if (!$post_type || !get_post_type_object($post_type)) {
            $this->add_notice(__('Invalid post type', 'aikon-role-manager'), 'error');
            return;
        }

        if (!$capability_type) {
            $this->add_notice(__('Capability type is required', 'aikon-role-manager'), 'warning');
            $this->add_error('capability_type', __('Capability type is required', 'aikon-role-manager'));
            return;
        }

        $this->manager->set_override($post_type, $capability_type);
        $this->add_notice(__('Override saved', 'aikon-role-manager'), 'success');

        wp_redirect(url_parser(['edit_post_type' => $post_type], ['action']));
        exit;
    }

    /**
     * @param Request $request
     * @return void
     */
    public function handle_remove_override(Request $request): void
    {
        $request->validate([
            'post_type' => 'string|minlength:1',
        ]);

        $post_type = sanitize_key($request->string('post_type'));

        if (!$post_type || !get_post_type_object($post_type)) {
            $this->add_notice(__('Invalid post type', 'aikon-role-manager'), 'error');
            return;
        }

        $this->manager->remove_override($post_type);
        $this->add_notice(__('Override removed', 'aikon-role-manager'), 'success');

        wp_redirect(url_parser([], ['edit_post_type', 'action', 'post_type']));
        exit;
    }

    /**
     * @return array<string, array{label: string, capabilities: array<string,string>}>
     */
    public function get_post_types_capabilities(): array
    {
        /** @var array<string,bool> */
        $args = config('post_type_query', [
            'show_ui' => true,
        ]);

        /** @var \WP_Post_Type[] */
        $post_types = get_post_types($args, 'objects');
        $result     = [];

        foreach ($post_types as $post_type) {
            /** @var array<string,string> */
            $capabilities          = (array) $post_type->cap;
            $result[$post_type->name] = [
                'label'        => $post_type->label,
                'capabilities' => $capabilities,
            ];
        }

        return $result;
    }

    public function render(): void
    {
        $edit_post_type = isset($_GET['edit_post_type']) && is_string($_GET['edit_post_type'])
            ? sanitize_key($_GET['edit_post_type'])
            : null;

        if ($edit_post_type && get_post_type_object($edit_post_type)) {
            $post_type_obj = get_post_type_object($edit_post_type);

            template('tab-post-types-edit', [
                'post_type'       => $edit_post_type,
                'label'           => $post_type_obj->label,
                'capabilities'    => (array) $post_type_obj->cap,
                'override'        => $this->manager->get_overrides()[$edit_post_type] ?? null,
                'has_override'    => $this->manager->has_override($edit_post_type),
                'tab'             => $this->slug,
                'errors'          => $this->errors(),
            ]);

            return;
        }

        template('tab-post-types', [
            'post_types' => $this->get_post_types_capabilities(),
            'overrides'  => $this->manager->get_overrides(),
            'tab'        => $this->slug,
        ]);
    }
}
