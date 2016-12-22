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

namespace RoaveTest\FunctionFQNReplacer\Lookup;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Type\SourceLocator;
use PHPUnit_Framework_TestCase;
use Roave\FunctionFQNReplacer\Lookup\FunctionExists;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \Roave\FunctionFQNReplacer\Lookup\FunctionExists
 */
final class FunctionExistsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider functionsProvider
     *
     * @param array  $existingFunctions
     * @param string $givenFunction
     * @param bool   $expectedResult
     *
     * @return void
     */
    public function testLocateFunction(array $existingFunctions, string $givenFunction, bool $expectedResult) : void
    {
        /* @var $locator SourceLocator|\PHPUnit_Framework_MockObject_MockObject */
        $locator = $this->createMock(SourceLocator::class);

        $locator
            ->expects(self::once())
            ->method('locateIdentifiersByType')
            ->with(
                self::isInstanceOf(FunctionReflector::class),
                self::equalTo(new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
            )
            ->willReturn(array_map(
                function (string $functionName) : Reflection {
                    /* @var $reflection Reflection|\PHPUnit_Framework_MockObject_MockObject */
                    $reflection = $this->createMock(Reflection::class);

                    $reflection->expects(self::any())->method('getName')->willReturn($functionName);

                    return $reflection;
                },
                $existingFunctions
            ));

        $exists = new FunctionExists($locator);

        self::assertSame($expectedResult, $exists->__invoke($givenFunction));
        self::assertSame($expectedResult, $exists->__invoke($givenFunction), 'Lookup is cached');
    }

    public function functionsProvider() : array
    {
        return [
            'empty'                     => [[], 'foo', false],
            'exact match'               => [['foo'], 'foo', true],
            'case sensitivity mismatch' => [['fOo'], 'FoO', true],
            'no match'                  => [['foo', 'bar', 'baz'], 'tab', false],
            'namespaced'                => [['foo\\bar'], 'foo\\bar', true],
            'namespace mismatch'        => [['bar\\baz'], 'baz', false],
        ];
    }
}
