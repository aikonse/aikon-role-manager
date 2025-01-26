<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->name('aikon-role-manager.php')
    ->exclude('vendor')
    ->exclude('node_modules')
    ->exclude('assets')
    ->exclude('templates'); // NOTICE: Nedd better configuration for template files, ignore for now

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'single_quote' => true,
        'no_unused_imports' => true,
        'no_extra_blank_lines' => true,
        'ordered_imports' => true,
    ])
    ->setFinder($finder);
