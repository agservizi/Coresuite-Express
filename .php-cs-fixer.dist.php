<?php
declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([__DIR__ . '/app', __DIR__ . '/config', __DIR__ . '/public', __DIR__ . '/scripts'])
    ->name('*.php')
    ->ignoreVCS(true)
    ->ignoreDotFiles(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
        'blank_line_before_statement' => ['statements' => ['return']],
        'concat_space' => ['spacing' => 'one'],
        'native_function_invocation' => ['include' => ['@compiler_optimized']],
    ])
    ->setFinder($finder);
