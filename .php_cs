<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__.'/src')
;

$rules = [
    '@Symfony' => true,
    'list_syntax' =>  ['syntax' => 'short'],
    'array_syntax' => ['syntax' => 'short'],
    'linebreak_after_opening_tag' => true,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    // Risky checks
    'psr4' => true,
    'ereg_to_preg' => true,
    'is_null' => true,
    'non_printable_character' => true,
    'random_api_migration' => true,
    'ternary_to_null_coalescing' => true,
];

return PhpCsFixer\Config::create()
    ->setRules($rules)
    ->setUsingCache(false)
    ->setFinder($finder)
    ->setRiskyAllowed(true)
;

