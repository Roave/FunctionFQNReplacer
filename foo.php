<?php

namespace Roave\FunctionFQNReplacer;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Exception\FunctionUndefined;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;

require_once __DIR__ . '/vendor/autoload.php';

$functionExists = function (string $functionName) : bool {
    $locator = new AggregateSourceLocator([
        new PhpInternalSourceLocator(),
        new EvaledCodeSourceLocator(),
        new AutoloadSourceLocator(),
        new SingleFileSourceLocator(__DIR__ . '/test-asset/mixed-function-references.php'),
    ]);

    $locator = new SingleFileSourceLocator(__DIR__ . '/test-asset/mixed-function-references.php');

//    $reflector = new FunctionReflector(new AggregateSourceLocator([
//        new PhpInternalSourceLocator(),
//        new EvaledCodeSourceLocator(),
//        new AutoloadSourceLocator(),
//        new SingleFileSourceLocator(__DIR__ . '/test-asset/mixed-function-references.php'),
//    ]));

    try {
//        $reflector->reflect($functionName);
        $locator->locateIdentifiersByType(
            new FunctionReflector($locator),
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        );

        return true;
    } catch (FunctionUndefined $ignored) {
        return false;
    }
};

$functions = [
    'call_user_func',
    'yadda',
    'base_namespace_function',
    'Bar\bar_namespace_function',
    'Foo\foo_namespace_function',
    'Foo\Baz\imported_function',
    'absolute_imported_function',
    'Foo\absolute_function',
    'Foo\Bar\relative_function_to_replace',
    'absolute_function_to_replace',
    'absolute_function',
    'base_namespace_function',
    'bar_namespace_function',
    'Foo\Foo\foo_namespace_function',
    'foo_namespace_function',
];

var_dump(array_combine(
    $functions,
    array_map($functionExists, $functions)
));
