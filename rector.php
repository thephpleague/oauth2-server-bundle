<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveEmptyClassMethodRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\Php83\Rector\ClassConst\AddTypeToConstRector;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Php84\Rector\Foreach_\ForeachToArrayAnyRector;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Symfony\Symfony80\Rector\Class_\RemoveEraseCredentialsRector;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    // uncomment to reach your current PHP version
    ->withPhpSets()
    // ->withCodeQualityLevel(0)
    ->withPreparedSets(typeDeclarations: true, deadCode: true)
    ->withComposerBased(symfony: true, phpunit: true, doctrine: true)
    // Rules newly triggered by the 2.0 PHP 8.4 / Symfony 7.4 floor bump are skipped for now:
    //  - ReadOnlyClassRector would introduce BC breaks (readonly classes on value objects/models).
    //  - RemoveEraseCredentialsRector / RemoveEmptyClassMethodRector would drop public methods that
    //    must stay while Symfony 7.4 is supported (e.g. ClientCredentialsUser::eraseCredentials()).
    //    RemoveEraseCredentialsRector only registers on Symfony >= 8.0, so the refactoring CI (which
    //    runs on the 7.4 floor) reports it as "never registered" — that note is harmless (exit 0).
    // The remaining (BC-safe) modernizations are deferred to a follow-up after PR #291 lands,
    // together with the invokable-command refactor, to avoid churn/conflicts in files #291 rewrites.
    ->withSkip([
        ReadOnlyClassRector::class,
        RemoveEraseCredentialsRector::class,
        RemoveEmptyClassMethodRector::class,
        AddOverrideAttributeToOverriddenMethodsRector::class,
        AddArrowFunctionReturnTypeRector::class,
        AddTypeToConstRector::class,
        ForeachToArrayAnyRector::class,
        NewMethodCallWithoutParenthesesRector::class,
    ])
;
