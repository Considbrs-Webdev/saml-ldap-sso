<?php

declare(strict_types=1);

use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\Closure\ClosureDelegatingCallToFirstClassCallableRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
    ])
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/vendor-prefixed',
        __DIR__ . '/tests',
        // WordPress hooks stay as [$this, 'method'] — first-class callables are not serializable.
        ArrayToFirstClassCallableRector::class,
        FunctionFirstClassCallableRector::class,
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class,
        ClosureDelegatingCallToFirstClassCallableRector::class,
        // Style-only; keep explicit property/class readonly where we chose it.
        ReadOnlyClassRector::class,
        ReadOnlyPropertyRector::class,
        ClosureToArrowFunctionRector::class,
        AddClosureVoidReturnTypeWhereNoReturnRector::class,
        // Methods that send headers then exit are not never-returning in PHPStan's WP model.
        ReturnNeverTypeRector::class,
    ])
    ->withPhpSets(php82: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0);
