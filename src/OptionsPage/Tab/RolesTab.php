<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Tab;

use Aikon\RoleManager\OptionsPage\Interfaces\TabInterface;
use Aikon\RoleManager\OptionsPage\Traits\HasTemplate;
use Aikon\RoleManager\OptionsPage\Traits\HasTitleAnSlug;
use Aikon\RoleManager\RoleManager;

class RolesTab implements TabInterface
{
    use HasTitleAnSlug;
    use HasTemplate;

    public function __construct(
        private RoleManager $manager
    ) {
        $this->title = 'Roles';
        $this->slug = 'roles';
    }

    public function handle(): void
    {

    }

    public function render(): void
    {

        $this->template('roles', [
            'roles' => $this->manager->current_roles(),
            'manager' => $this->manager,
        ]);
    }
}
