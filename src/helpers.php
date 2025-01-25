<?php

declare(strict_types=1);

namespace Aikon\RoleManager;

/**
 * Render the template file and pass the arguments
 *
 * @param string $template
 * @param array<string, mixed> $data
 * @return void
 */
function template(string $template, array $data = []): void
{
    $template_file = ARM_TEMPLATE_PATH . DIRECTORY_SEPARATOR . $template . '.php';
    if (!file_exists($template_file)) {
        throw new \Exception("Template {$template} not found.");
    }

    extract($data);
    include $template_file;
}

/**
 * Url parser
 * - Add and remove query parameters
 * @param array<string, mixed> $query Array of query parameters to add
 * @param string[] $remove_query Array of query parameters to remove
 * @return string $url The new url
 */
function url_parser(array $query = [], array $remove_query = []): string
{
    // @phpstan-ignore-next-line
    $url = (empty($_SERVER['HTTPS']) ? 'http' : 'https') . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $url = add_query_arg($query, $url);
    $url = remove_query_arg($remove_query, $url);

    return $url;
}
