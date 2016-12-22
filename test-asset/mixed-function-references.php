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
// now the same, but with case sensitivity changes
namespace Foo {
    use function Baz\imported_Function;
    use function absolute_Imported_function;
    // not replaced
    IMPORTED_FUNCTION();
    // not replaced
    ABSOLUTE_IMPORTED_FUNCTION();
    // not replaced
    \FOO\ABSOLUTE_FUNCTION();
    // not replaced
    BAR\RELATIVE_FUNCTION_TO_REPLACE();
    // replaced with absolute reference to \absolute_function_to_replace
    ABSOLUTE_FUNCTION_TO_REPLACE();
    // not replaced
    \ABSOLUTE_FUNCTION();
    // functions existing in \
    // replaced with absolute reference to \base_namespace_function
    BASE_NAMESPACE_FUNCTION();
    // not replaced
    \BASE_NAMESPACE_FUNCTION();
    // functions existing in \Bar
    // not replaced
    \BAR\BAR_NAMESPACE_FUNCTION();
    // not replaced (wrong reference)
    BAR\BAR_NAMESPACE_FUNCTION();
    // replaced with absolute reference to \bar_namespace_function
    BAR_NAMESPACE_FUNCTION();
    // functions existing in Foo
    // not replaced
    \FOO\FOO_NAMESPACE_FUNCTION();
    // not replaced (wrong reference)
    FOO\FOO_NAMESPACE_FUNCTION();
    // replaced with absolute reference to \Foo\foo_namespace_function
    FOO_NAMESPACE_FUNCTION();
}