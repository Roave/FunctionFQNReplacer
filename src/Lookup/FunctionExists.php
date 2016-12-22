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

namespace Roave\FunctionFQNReplacer\Lookup;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Type\SourceLocator;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
final class FunctionExists
{
    /**
     * @var array|null
     */
    private $cachedExistingFunctionNames;

    /**
     * @var SourceLocator
     */
    private $locator;

    public function __construct(SourceLocator $locator)
    {
        $this->locator = $locator;
    }

    public function __invoke(string $functionFQN) : bool
    {
        return array_key_exists(strtolower($functionFQN), $this->existingFunctions());
    }

    private function existingFunctions() : array
    {
        return $this->cachedExistingFunctionNames ?? $this->cachedExistingFunctionNames = array_flip(array_map(
            function (Reflection $reflection) {
                return strtolower($reflection->getName());
            },
            $this->locator->locateIdentifiersByType(
                new FunctionReflector($this->locator),
                new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
            )
        ));
    }
}
