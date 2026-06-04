<?php

declare(strict_types=1);

namespace Aikon\RoleManager\Tests\Unit\OptionsPage;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\OptionsPage;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class OptionsPageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    private function makeTab(string $slug, string $title = 'Test Tab'): TabInterface
    {
        $tab = \Mockery::mock(TabInterface::class);
        $tab->allows('slug')->andReturn($slug);
        $tab->allows('title')->andReturn($title);
        $tab->allows('icon')->andReturn('dashicons-admin-users');
        return $tab;
    }

    public function test_requires_manage_options_capability_to_access(): void
    {
        Functions\expect('add_users_page')
            ->once()
            ->with(
                \Mockery::any(),
                \Mockery::any(),
                'manage_options',
                \Mockery::any(),
                \Mockery::any()
            );

        new OptionsPage([$this->makeTab('roles')]);
    }

    public function test_registers_menu_page_under_users(): void
    {
        Functions\expect('add_users_page')
            ->once()
            ->with(
                'Aikon Role Manager',
                'Role Manager',
                'manage_options',
                'aikon-role-manager',
                \Mockery::type('array')
            );

        new OptionsPage([$this->makeTab('roles')]);
    }

    public function test_is_not_current_page_when_get_param_absent(): void
    {
        Functions\expect('add_users_page')->once();

        unset($_GET['page']);

        $page = new OptionsPage([$this->makeTab('roles')]);

        $this->assertFalse($page->is_current_page());
    }

    public function test_is_current_page_when_get_param_matches_slug(): void
    {
        Functions\expect('add_users_page')->once();
        Functions\expect('sanitize_key')
            ->with('aikon-role-manager')
            ->andReturn('aikon-role-manager');

        // Construct without the GET param to avoid triggering handle() + assets() in the constructor
        $page = new OptionsPage([$this->makeTab('roles')]);

        $_GET['page'] = 'aikon-role-manager';
        $this->assertTrue($page->is_current_page());

        unset($_GET['page']);
    }

    public function test_is_not_current_page_when_get_param_does_not_match(): void
    {
        Functions\expect('add_users_page')->once();
        Functions\expect('sanitize_key')
            ->with('some-other-page')
            ->andReturn('some-other-page');

        $_GET['page'] = 'some-other-page';

        $page = new OptionsPage([$this->makeTab('roles')]);

        $this->assertFalse($page->is_current_page());

        unset($_GET['page']);
    }

    public function test_current_tab_defaults_to_first_view(): void
    {
        Functions\expect('add_users_page')->once();

        unset($_GET['tab']);

        $page = new OptionsPage([
            $this->makeTab('roles'),
            $this->makeTab('capabilities'),
        ]);

        $this->assertSame('roles', $page->current_tab());
    }

    public function test_current_tab_returns_tab_from_get_param(): void
    {
        Functions\expect('add_users_page')->once();
        Functions\expect('sanitize_key')
            ->with('capabilities')
            ->andReturn('capabilities');

        $_GET['tab'] = 'capabilities';

        $page = new OptionsPage([
            $this->makeTab('roles'),
            $this->makeTab('capabilities'),
        ]);

        $this->assertSame('capabilities', $page->current_tab());

        unset($_GET['tab']);
    }

    public function test_current_tab_falls_back_to_default_for_unknown_tab(): void
    {
        Functions\expect('add_users_page')->once();
        Functions\expect('sanitize_key')
            ->with('nonexistent')
            ->andReturn('nonexistent');

        $_GET['tab'] = 'nonexistent';

        $page = new OptionsPage([
            $this->makeTab('roles'),
            $this->makeTab('capabilities'),
        ]);

        $this->assertSame('roles', $page->current_tab());

        unset($_GET['tab']);
    }
}
