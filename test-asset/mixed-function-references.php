<?php

namespace {
    function base_namespace_function()
    {
    }
}
namespace Bar {
    function bar_namespace_function()
    {
    }
}
namespace Foo {
    function foo_namespace_function()
    {
    }
}
namespace Foo {
    use function Baz\imported_function;
    use function absolute_imported_function;
    // not replaced
    imported_function();
    // not replaced
    absolute_imported_function();
    // not replaced
    \Foo\absolute_function();
    // not replaced
    Bar\relative_function_to_replace();
    // replaced with absolute reference to \absolute_function_to_replace
    absolute_function_to_replace();
    // not replaced
    \absolute_function();
    // functions existing in \
    // replaced with absolute reference to \base_namespace_function
    base_namespace_function();
    // not replaced
    \base_namespace_function();
    // functions existing in \Bar
    // not replaced
    \Bar\bar_namespace_function();
    // not replaced (wrong reference)
    Bar\bar_namespace_function();
    // replaced with absolute reference to \bar_namespace_function
    bar_namespace_function();
    // functions existing in Foo
    // not replaced
    \Foo\foo_namespace_function();
    // not replaced (wrong reference)
    Foo\foo_namespace_function();
    // replaced with absolute reference to \Foo\foo_namespace_function
    foo_namespace_function();
}