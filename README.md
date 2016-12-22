# Function FQN Replacer

This utility provides a way to replace relative references of functions in
function calls with absolute references.

## Rationale

As explained in [this twitter convo](https://twitter.com/Ocramius/status/811504929357660160) and
[this article](https://veewee.github.io/blog/optimizing-php-performance-by-fq-function-calls/),
PHP is resolving relative function references at call time.

This is relatively normal, as PHP, by design, cannot make decisions on which file defines which
functions, because all opcode caching is local to single scripts, and not to a project.

In addition to that, PHP can only apply optimizations about internal function calls when those
calls happen either in the global namespace, or are fully qualified name (FQN) calls.

Here's an example of the opcodes generated for an `call_user_func()` call without and with
FQN reference:


```php
<?php

namespace Foo {
    call_user_func('foo');
}
```

Opcodes:

```
line     #* E I O op                           fetch          ext  return  operands
-------------------------------------------------------------------------------------
   4     0  E >   INIT_NS_FCALL_BY_NAME                                    
         1        SEND_VAL_EX                                              'foo'
         2        DO_FCALL                                      0          
   5     3      > RETURN                                                   1
```


```php
<?php

namespace Foo {
    // this is a FQN reference:
    \call_user_func('foo');
}
```

```
line     #* E I O op                           fetch          ext  return  operands
-------------------------------------------------------------------------------------
   4     0  E >   INIT_USER_CALL                                0          'call_user_func', 'foo'
         1        DO_FCALL                                      0          
   5     2      > RETURN                                                   1
```

As you can see, `INIT_NS_FCALL_BY_NAME` is gone. This is one of the many optimizations
applied by PHP 7 and newer versions (see
[zend_compile.c](https://github.com/php/php-src/blob/PHP-7.1/Zend/zend_compile.c) for more
examples).