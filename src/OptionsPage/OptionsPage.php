<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;

use function Aikon\RoleManager\template;

class OptionsPage
{
    /** Options page title */
    protected string $page_title = 'Aikon Role Manager';

    /** Options page tagline */
    protected string $tagline = 'Manage roles and capabilities';

    /** Options page slug */
    protected string $page_slug = 'aikon-role-manager';

    /** Options page menu title */
    protected string $menu_title = 'Role Manager';

    protected string $default_tab;

    /** @var array<string, TabInterface> */
    private array $views;

    /**
     * @param TabInterface[] $views
    */
    public function __construct(
        array $views
    ) {
        foreach ($views as $view) {
            $this->views[$view->slug()] = $view;
        }
        $this->default_tab = array_keys($this->views)[0];

        // Add the options page — must happen before handle() so the page is
        // always registered even if handle() throws.
        $hook = add_users_page(
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->page_slug,
            [$this, 'page']
        );

        // Handle form actions and enqueue assets on the page-specific load hook.
        // This fires after the page is registered and only for authorised users.
        if ($hook) {
            add_action('load-' . $hook, function (): void {
                $slug = $this->current_tab();
                $tab  = $this->views[$slug];

                if (!$tab->visible()) {
                    wp_redirect(admin_url('users.php?page=' . $this->page_slug));
                    exit;
                }

                $tab->handle();
                $this->assets();
            });
        }
    }

    public function is_current_page(): bool
    {
        return isset($_GET['page']) &&
            is_string($_GET['page']) &&
            sanitize_key($_GET['page']) === $this->page_slug;
    }

    public function current_tab(): string
    {
        $tabFromUrl = isset($_GET['tab']) && is_string($_GET['tab'])
            ? sanitize_key($_GET['tab'])
            : $this->default_tab;

        $tab = in_array(
            $tabFromUrl,
            array_keys($this->views)
        ) ? $tabFromUrl : $this->default_tab;

        return $tab;
    }

    /**
     * Enqueue the assets
     *
     * @return void
     */
    public function assets(): void
    {
        /** @var array{dependencies: array<string>, version: string} */
        $asset_config = require AIKON_ROLE_MANAGER_PATH . 'assets/build/main.asset.php';

        [
            'dependencies'  => $dependencies,
            'version'       => $version
        ] = $asset_config;

        add_action('admin_enqueue_scripts', function () use ($version, $dependencies): void {
            wp_enqueue_style('aikon-roles-manager/main', AIKON_ROLE_MANAGER_URL . 'assets/build/main.css', $dependencies, $version);
            wp_enqueue_script('aikon-roles-manager/main', AIKON_ROLE_MANAGER_URL . 'assets/build/main.js', $dependencies, $version, true);
        });
    }

    /**
     * Renders the options page
     *
     * @return void
     */
    public function page(): void
    {
        $tab = $this->current_tab();

        template('page', [
            'page' => $this,
            'title' => $this->page_title,
            'tagline' => $this->tagline,
            'tab' => $this->views[$tab],
            'current_tab' => $tab,
        ]);
    }

    /**
     * Renders a tab navigation from the views array
     *
     * @param string $current_tab The current tab
     * @return void
     */
    public function render_tab_nav(string $current_tab): void
    {
        $tabs = [];
        foreach ($this->views as $view) {
            if (!$view->visible()) {
                continue;
            }
            $tabs[] = [
                'title' => $view->title(),
                'slug'  => $view->slug(),
                'icon'  => $view->icon(),
            ];
        }

        template('partials/page-tabs', [
            'page' => $this->page_slug,
            'tabs' => $tabs,
            'current_tab' => $current_tab,
        ]);
    }
}
