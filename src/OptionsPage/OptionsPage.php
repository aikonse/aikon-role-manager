<?php

namespace Aikon\RoleManager\OptionsPage;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;

class OptionsPage
{
    protected string $page_title = 'Aikon Roles Manager';

    protected string $page_slug = 'aikon-roles-manager';

    protected string $menu_title = 'Roles Manager';

    protected string $tagline = 'Manage roles and capabilities';

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

        add_users_page(
            $this->page_title,
            $this->menu_title,
            'manage_options',
            $this->page_slug,
            [$this, 'page']
        );
    }

    public function page(): void
    {
        $tabFromUrl = $_GET['tab'] ?? $this->default_tab;
        $tab = in_array(
            $tabFromUrl,
            array_keys($this->views)
        ) ? $tabFromUrl : $this->default_tab;

        //$this->views[$tab]->handle();
        ?>
        <div class="wrap">
            <h1><?php echo $this->page_title;?></h1>
            <p><?php echo $this->tagline;?></p>
            <?php $this->render_tab_nav($tab); ?>
            <div class="tab-content">
                <?php $this->views[$tab]->render(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renders a tab navigation from the views array
     *
     * @param string $current_tab The current tab
     * @return void
     */
    private function render_tab_nav(string $current_tab): void
    {
        ?>
        <h2 class="nav-tab-wrapper">
            <?php foreach ($this->views as $tab => $view) : ?>
                <a href="?page=<?php echo $this->page_slug;?>&tab=<?php echo $tab; ?>" class="nav-tab <?php echo $current_tab === $tab ? 'nav-tab-active' : ''; ?>">
                    <?php echo $view->title(); ?>
                </a>
            <?php endforeach; ?>
        </h2>
        <?php
    }
}
