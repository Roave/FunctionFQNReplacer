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

namespace Foo
{
    use function Baz\imported_function;
    use function absolute_imported_function;

    imported_function(); // not replaced
    absolute_imported_function(); // not replaced

    \Foo\absolute_function(); // not replaced
    Bar\relative_function_to_replace(); // not replaced

    absolute_function_to_replace(); // replaced with absolute reference to \absolute_function_to_replace
    \absolute_function(); // not replaced

    // functions existing in \
    base_namespace_function(); // replaced with absolute reference to \base_namespace_function
    \base_namespace_function(); // not replaced

    // functions existing in \Bar
    \Bar\bar_namespace_function(); // not replaced
    Bar\bar_namespace_function(); // not replaced (wrong reference)
    bar_namespace_function(); // replaced with absolute reference to \bar_namespace_function

    // functions existing in Foo
    \Foo\foo_namespace_function(); // not replaced
    Foo\foo_namespace_function(); // not replaced (wrong reference)
    foo_namespace_function(); // replaced with absolute reference to \Foo\foo_namespace_function
}
