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

namespace RoaveTest\FunctionFQNReplacer\Iterator;

use PHPUnit_Framework_TestCase;
use Roave\FunctionFQNReplacer\Iterator\PhpFilesInDirectoryIterator;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 *
 * @covers \Roave\FunctionFQNReplacer\Iterator\PhpFilesInDirectoryIterator
 */
final class PhpFilesInDirectoryIteratorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider pathsProvider
     *
     * @param string[] $paths
     */
    public function testProducesStringsIterator(array $paths) : void
    {
        $resolvedPaths = PhpFilesInDirectoryIterator::iterate($paths);

        $this->assertInstanceOf(\Iterator::class, $resolvedPaths);

        foreach ($resolvedPaths as $path) {
            $this->assertInternalType('string', $path);
        }
    }

    /**
     * @dataProvider pathsProvider
     *
     * @param string[] $paths
     */
    public function testDiscoversCorrectAmountOfFiles(array $paths, $expectedCount) : void
    {
        $resolvedPaths = PhpFilesInDirectoryIterator::iterate($paths);
        $count         = 0;

        foreach ($resolvedPaths as $path) {
            $count += 1;
        }

        self::assertSame($expectedCount, $count);
    }

    /**
     * Data provider
     *
     * @return string[][][]|int[][]
     */
    public function pathsProvider()
    {
        return [
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile/1.hh'],
                0
            ],
            [
                [
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile/1.hh',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile/1.php',
                ],
                1
            ],
            [
                [
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile/1.hh',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile/1.php',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/EmptyDirectory',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile',
                ],
                2
            ],
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest'],
                3
            ],
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/EmptyDirectory'],
                0
            ],
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile'],
                1
            ],
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithTwoPhpFiles'],
                2
            ],
            [
                [__DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile'],
                0
            ],
            [
                [
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile',
                ],
                1
            ],
            [
                [
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOnePhpFile',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithOneHhFile',
                    __DIR__ . '/../../test-asset/Iterator/PhpFilesInDirectoryIteratorTest/DirWithTwoPhpFiles',
                ],
                3
            ],
        ];
    }
}
