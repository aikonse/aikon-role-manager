<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tabs;

use Aikon\RoleManager\Manager\RoleManager;
use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;

use function Aikon\RoleManager\template;

class CapabilitiesTab implements TabInterface
{
    use HasTitleAnSlug;

    public function __construct(
        private RoleManager $manager
    ) {
        $this->title = 'Capabilities';
        $this->slug = 'capabilities';
        $this->icon = 'dashicons-privacy';
    }

    public function handle(): void
    {

    }

    public function render(): void
    {
        template('tab-capabilities', [
            'manager' => $this->manager,
        ]);
    }
}
