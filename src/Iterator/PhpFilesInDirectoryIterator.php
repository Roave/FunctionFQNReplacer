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

namespace Roave\FunctionFQNReplacer\Iterator;

use Iterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
final class PhpFilesInDirectoryIterator
{
    private function __construct()
    {
    }

    /**
     * @param iterable|string[] $directories
     *
     * @return iterable|string[] paths to PHP files
     */
    public static function iterate(iterable $directories) : iterable
    {
        $appendIterator = new \AppendIterator();

        array_map(
            [$appendIterator, 'append'],
            array_map([self::class, 'buildRegexDirectoryIterator'], $directories)
        );

        foreach ($appendIterator as $entry) {
            yield $entry[0];
        }
    }

    private static function buildRegexDirectoryIterator(string $path) : Iterator
    {
        if (is_file($path)) {
            return new RegexIterator(
                new \ArrayIterator([$path]),
                '/^.+\.php$/i',
                \RecursiveRegexIterator::GET_MATCH
            );
        }

        return new RegexIterator(
            new RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            ),
            '/^.+\.php$/i',
            \RecursiveRegexIterator::GET_MATCH
        );
    }
}
