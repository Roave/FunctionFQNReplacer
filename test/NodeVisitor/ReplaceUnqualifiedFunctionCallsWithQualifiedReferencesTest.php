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
                    $functionName,
                    [
                        'base_namespace_function',
                        'Bar\\bar_namespace_function',
                        'Foo\\foo_namespace_function',
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
}
