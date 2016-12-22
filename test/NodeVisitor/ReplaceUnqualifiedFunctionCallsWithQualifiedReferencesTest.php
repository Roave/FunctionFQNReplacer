<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license.
 */

declare(strict_types=1);

namespace RoaveTest\FunctionFQNReplacer\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use PHPUnit_Framework_TestCase;
use Roave\FunctionFQNReplacer\NodeVisitor\ReplaceUnqualifiedFunctionCallsWithQualifiedReferences;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \Roave\FunctionFQNReplacer\NodeVisitor\ReplaceUnqualifiedFunctionCallsWithQualifiedReferences
 */
final class ReplaceUnqualifiedFunctionCallsWithQualifiedReferencesTest extends PHPUnit_Framework_TestCase
{
    public function testCompleteCodeReplacementTest()
    {
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(
            function (string $functionName) : bool {
                return in_array(
                    strtolower($functionName),
                    [
                        'base_namespace_function',
                        'bar\\bar_namespace_function',
                        'foo\\foo_namespace_function',
                    ],
                    true
                );
            }
        ));

        self::assertStringEqualsFile(
            __DIR__ . '/../../test-asset/mixed-function-references.expect.php',
            "<?php\n" . (new Standard())->prettyPrint($traverser->traverse(
                (new ParserFactory())
                    ->create(ParserFactory::ONLY_PHP7)
                    ->parse(file_get_contents(__DIR__ . '/../../test-asset/mixed-function-references.php'))
            ))
        );
    }

    public function testDoesNotReplaceUnknownNodeTypes()
    {
        /* @var $node Node */
        $node = $this->createMock(Node::class);

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function () {
            self::fail('Not expected to be called');
        });

        self::assertNull($visitor->beforeTraverse([$node]));
        self::assertNull($visitor->enterNode($node));
        self::assertNull($visitor->leaveNode($node));
        self::assertNull($visitor->afterTraverse([$node]));
    }

    public function testReplacesFunctionCallInGlobalNamespace()
    {
        $functionCall = new FuncCall(new Name('foo'));

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function (string $function) : bool {
            self::assertSame('foo', $function);

            return true;
        });

        self::assertNull($visitor->beforeTraverse([$functionCall]));
        self::assertNull($visitor->enterNode($functionCall));

        $replaced = $visitor->leaveNode($functionCall);

        self::assertNotSame($functionCall, $replaced);
        self::assertEquals(new FuncCall(new FullyQualified('foo')), $replaced);
        self::assertNull($visitor->afterTraverse([$functionCall]));
    }

    public function testReplacesUnknownFunctionCallInNamespace()
    {
        $namespace    = new Namespace_(new Name('bar'));
        $functionCall = new FuncCall(new Name('foo'));

        $namespace->stmts[] = $functionCall;

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function (string $function) : bool {
            self::assertSame('bar\\foo', $function);

            return false;
        });

        self::assertNull($visitor->beforeTraverse([$namespace]));
        self::assertNull($visitor->enterNode($namespace));
        self::assertNull($visitor->enterNode($functionCall));

        $replaced = $visitor->leaveNode($functionCall);

        self::assertNotSame($functionCall, $replaced);
        self::assertEquals(new FuncCall(new FullyQualified('foo')), $replaced);

        $visitor->leaveNode($namespace);
        self::assertNull($visitor->afterTraverse([$namespace]));
    }

    public function testReplacesKnownFunctionCallInNamespace()
    {
        $namespace    = new Namespace_(new Name('bar'));
        $functionCall = new FuncCall(new Name('foo'));

        $namespace->stmts[] = $functionCall;

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function (string $function) : bool {
            self::assertSame('bar\\foo', $function);

            return true;
        });

        self::assertNull($visitor->beforeTraverse([$namespace]));
        self::assertNull($visitor->enterNode($namespace));
        self::assertNull($visitor->enterNode($functionCall));

        $replaced = $visitor->leaveNode($functionCall);

        self::assertNotSame($functionCall, $replaced);
        self::assertEquals(new FuncCall(new FullyQualified('bar\\foo')), $replaced);

        $visitor->leaveNode($namespace);
        self::assertNull($visitor->afterTraverse([$namespace]));
    }

    public function testDoesNotReplaceUnknownImportedFunction()
    {
        $namespace    = new Namespace_(new Name('bar'));
        $use          = new Use_([new UseUse(new Name('baz\\foo'))], Use_::TYPE_FUNCTION);
        $functionCall = new FuncCall(new Name('foo'));

        $namespace->stmts[] = $use;
        $namespace->stmts[] = $functionCall;

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function () {
            self::fail('Not expected to be called');
        });

        self::assertNull($visitor->beforeTraverse([$namespace]));
        self::assertNull($visitor->enterNode($namespace));
        self::assertNull($visitor->enterNode($use));
        self::assertNull($visitor->leaveNode($use));
        self::assertNull($visitor->enterNode($functionCall));

        $replaced = $visitor->leaveNode($functionCall);

        self::assertNotSame($functionCall, $replaced);
        self::assertEquals(new FuncCall(new Name('foo')), $replaced);

        $visitor->leaveNode($namespace);
        self::assertNull($visitor->afterTraverse([$namespace]));
    }

    public function testDoesNotReplaceUnknownImportedFunctionWithDifferentCasing()
    {
        $namespace    = new Namespace_(new Name('bar'));
        $use          = new Use_([new UseUse(new Name('baz\\foo'))], Use_::TYPE_FUNCTION);
        $functionCall = new FuncCall(new Name('FOO'));

        $namespace->stmts[] = $use;
        $namespace->stmts[] = $functionCall;

        $visitor = new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(function () {
            self::fail('Not expected to be called');
        });

        self::assertNull($visitor->beforeTraverse([$namespace]));
        self::assertNull($visitor->enterNode($namespace));
        self::assertNull($visitor->enterNode($use));
        self::assertNull($visitor->leaveNode($use));
        self::assertNull($visitor->enterNode($functionCall));

        $replaced = $visitor->leaveNode($functionCall);

        self::assertNotSame($functionCall, $replaced);
        self::assertEquals(new FuncCall(new Name('FOO')), $replaced);

        $visitor->leaveNode($namespace);
        self::assertNull($visitor->afterTraverse([$namespace]));
    }
}
