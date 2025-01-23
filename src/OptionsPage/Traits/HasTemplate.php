<?php

namespace Aikon\RoleManager\OptionsPage\Traits;

trait HasTemplate
{
    /**
     * Render the template file and pass the arguments
     *
     * @return void
     */
    public function template(string $template, array $args): void
    {
        $template_path = ARM_TEMPLATE_PATH . DIRECTORY_SEPARATOR . $template . '.php';

        if (strlen($template) === 0) {
            throw new \Exception('Template name is empty');
        }

        if (!file_exists($template_path)) {
            throw new \Exception('Template file does not exist (' . $template_path . ')');
        }

        $args['view'] = $this;

        extract($args);
        include $template_path;
    }
}
