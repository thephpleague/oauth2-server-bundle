<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets()
    ->withPreparedSets(typeDeclarations: true, deadCode: true)
    ->withComposerBased(symfony: true, phpunit: true, doctrine: true)
    // Rules newly triggered by the 2.0 PHP 8.2 / Symfony 7.4 floor bump are skipped for now:
    //  - ReadOnlyClassRector would introduce BC breaks (readonly classes on value objects/models).
    ->withSkip([
        ReadOnlyClassRector::class,
    ])
;
