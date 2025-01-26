<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;

use function Aikon\RoleManager\template;

class OptionsPage
{
    /** Options page title */
    protected string $page_title = 'Aikon Roles Manager';

    /** Options page tagline */
    protected string $tagline = 'Manage roles and capabilities';

    /** Options page slug */
    protected string $page_slug = 'aikon-roles-manager';

    /** Options page menu title */
    protected string $menu_title = 'Roles Manager';

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

        // Load assets and handle actions
        if ($this->is_current_page()) {
            $slug = $this->current_tab();
            $this->views[$slug]->handle();
            $this->assets();
        }

        // Add the options page
        add_users_page(
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->page_slug,
            [$this, 'page']
        );
    }

    public function is_current_page(): bool
    {
        return isset($_GET['page']) && $_GET['page'] === $this->page_slug;
    }

    public function current_tab(): string
    {
        $tabFromUrl = $_GET['tab'] ?? null;
        $tabFromUrl = is_string($tabFromUrl)
            ? $tabFromUrl
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
        $asset_config = require ARM_PATH . 'assets/build/main.asset.php';

        [
            'dependencies'  => $dependencies,
            'version'       => $version
        ] = $asset_config;

        add_action('admin_enqueue_scripts', function () use ($version, $dependencies): void {
            wp_enqueue_style('aikon-roles-manager/main', ARM_URL . 'assets/build/main.css', $dependencies, $version);
            wp_enqueue_script('aikon-roles-manager/main', ARM_URL . 'assets/build/main.js', $dependencies, $version, true);
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
        do_action('admin_notices');

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
        foreach ($this->views as $slug => $view) {
            $tabs[] = [
                'title' => $view->title(),
                'slug' => $slug,
                'icon' => $view->icon(),
            ];
        }

        template('page-tabs', [
            'page' => $this->page_slug,
            'tabs' => $tabs,
            'current_tab' => $current_tab,
        ]);
    }
}
