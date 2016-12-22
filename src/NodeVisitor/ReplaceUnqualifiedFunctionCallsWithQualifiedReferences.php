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

namespace Roave\FunctionFQNReplacer\NodeVisitor;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;

/**
 * @author Marco Pivetta <ocramius@gmail.com>
 * @license MIT
 */
final class ReplaceUnqualifiedFunctionCallsWithQualifiedReferences implements NodeVisitor
{
    /**
     * @var string|null
     */
    private $currentNamespace;

    /**
     * @var callable
     */
    private $functionExists;

    /**
     * @var string[]
     */
    private $importedFunctionNames = [];

    public function __construct(callable $functionExists)
    {
        $this->functionExists = $functionExists;
    }

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes) : void
    {
        $this->currentNamespace = null;
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node) : void
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace      = (string) $node->name;
            $this->importedFunctionNames = [];
        }

        if ($node instanceof Use_ && $node->type === Use_::TYPE_FUNCTION) {
            foreach ($node->uses as $use) {
                $this->importedFunctionNames[] = strtolower($use->alias);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function leaveNode(Node $node) : ?Node
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace      = null;
            $this->importedFunctionNames = [];
        }

        if ($node instanceof FuncCall && $this->functionCallIsNotQualifiedName($node)) {
            return $this->replaceFunctionCall($node);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function afterTraverse(array $nodes) : void
    {
        $this->currentNamespace = null;
    }

    private function functionCallIsNotQualifiedName(FuncCall $node) : bool
    {
        $name = $node->name;

        return $name instanceof Name && $name->isUnqualified();
    }

    private function replaceFunctionCall(FuncCall $node) : FuncCall
    {
        $newNode = clone $node;

        $newNode->name = $this->replaceFunctionName($node->name);

        return $newNode;
    }

    private function replaceFunctionName(Name $originalName) : Name
    {
        $originalNameString             = (string) $originalName;
        $currentNamespaceNamespacedName = ltrim($this->currentNamespace . '\\' . $originalNameString, '\\');

        if (($this->functionExists)($currentNamespaceNamespacedName)) {
            return new FullyQualified($currentNamespaceNamespacedName);
        }

        if ($this->aliasIsImported($originalName)) {
            return $originalName;
        }

        return new FullyQualified($originalNameString);
    }

    private function aliasIsImported(Name $originalName) : bool
    {
        return in_array(strtolower((string) $originalName), $this->importedFunctionNames, true);
    }
}
