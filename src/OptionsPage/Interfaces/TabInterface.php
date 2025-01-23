<?php

declare(strict_types=1);

namespace Aikon\RoleManager\OptionsPage\Interfaces;

interface TabInterface
{
    public function title(): string;
    public function slug(): string;
    public function handle(): void;
    public function render(): void;

    /**
     * Render the template file and pass the arguments
     * @param string $template
     * @param array<string,mixed> $args
     * @return void
     */
    public function template(string $template, array $args): void;
}
