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

## Benchmark

All of the above sounds like a silly and pointless micro-optimization, but it makes a huge
difference when it comes to commonly and widespread libraries.

In order to state the point more clearly, some benchmarks are provided with this package.

Simply run `php -n ./vendor/bin/phpbench run --revs=1000 --iterations=10 --warmup=2 --report=aggregate`
from within this project:

```
$ php -n ./vendor/bin/phpbench run --revs=1000 --iterations=10 --warmup=2 --report=aggregate
PhpBench 0.13.0. Running benchmarks.
Using configuration file: FunctionFQNReplacer/phpbench.json

\RoaveBench\FunctionFQNReplacer\AbsoluteFunctionReferenceBench

    benchCallUserFuncWithRelativeReferenceI2 P0 	[μ Mo]/r: 2.603 2.586 (μs) [μSD μRSD]/r: 0.034μs 1.31%
    benchCallUserFuncWithAbsoluteReferenceI2 P0 	[μ Mo]/r: 1.767 1.635 (μs) [    benchCallUserFuncWithAbsoluteReferenceR3 I2 P0 	[μ Mo]/r: 1.793 1.799 (μs) [μSD μRSD]/r: 0.009μs 0.53%

2 subjects, 6 iterations, 200 revs, 0 rejects
(best [mean mode] worst) = 1.780 [2.198 2.192] 1.800 (μs)
⅀T: 13.190μs μSD/r 0.022μs μRSD/r: 0.916%
suite: 133a2c6cc9c7a295d7b89ff84b2cfff4f39d8935, date: 2016-12-22, stime: 23:10:51
+----------------------------------------+---------+---------+---------+---------+---------+--------+---------+
| subject                                | best    | mean    | mode    | worst   | stdev   | rstdev | diff    |
+----------------------------------------+---------+---------+---------+---------+---------+--------+---------+
| benchCallUserFuncWithRelativeReference | 2.642μs | 2.729μs | 2.694μs | 2.839μs | 0.062μs | 2.28%  | +68.78% |
| benchCallUserFuncWithAbsoluteReference | 1.577μs | 1.617μs | 1.610μs | 1.688μs | 0.029μs | 1.77%  | 0.00%   |
+----------------------------------------+---------+---------+---------+---------+---------+--------+---------+
```

As you can see, `call_user_func()` vs `\call_user_func()` is a sensible difference.

Feel free to add benchmarks to the [`benchmark/`](benchmark) directory.

## Installation

This project is not meant to be run as a dependency: please install it as standalone.

```php
composer create-project roave/function-fqn-replacer
```

## Usage

Please beware that this project the internal code generator of
[`nikic/php-parser`](https://github.com/nikic/PHP-Parser). This means that it
**will break your coding style** when recreating the sources of your PHP files. This is a
[known and unresolved issue](https://github.com/nikic/PHP-Parser/issues/41).

```sh
./function-fqn-replacer path/to/project/files path/to/existing/functions another/path/to/existing/functions
```

The first argument is the path to the directory where you want the FQN references to be
replaced.

Additional parameters are the paths where function definitions can be found. The tool
needs to know these in order to avoid replacing functions that may not be internal.

## Alternatives

This tool was built in a rush, but is quite well tested and based on solid background
foundations. Still, it will modify whitespace alignment in your source files due to
technical limitations of the current PHP AST parser.

If you don't like these consequences, consider using 
[`nilportugues/php-backslasher`](https://github.com/nilportugues/php-backslasher) instead,
which has been around for more time, and uses a lower level implementation of token
replacement which preserves whitespace alignment
