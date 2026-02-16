<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Operator\ConcatSpaceFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ])
    ->withPreparedSets(true)
    ->withSkip([
        ConcatSpaceFixer::class
    ]);