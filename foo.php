<?php

namespace Roave\FunctionFQNReplacer;

use BetterReflection\Identifier\IdentifierType;
use BetterReflection\Reflection\Reflection;
use BetterReflection\Reflector\FunctionReflector;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;

require_once __DIR__ . '/vendor/autoload.php';

$declaredFunctionNames = function () : array {
    $locator = new AggregateSourceLocator([
        new PhpInternalSourceLocator(),
        new EvaledCodeSourceLocator(),
        //new AutoloadSourceLocator(),
        //new DirectoriesSourceLocator([__DIR__ . '/vendor', __DIR__ . '/src']),
        new SingleFileSourceLocator(__DIR__ . '/test-asset/mixed-function-references.php'),
    ]);

    return array_values(array_map(
        function (Reflection $reflection) {
            return strtolower($reflection->getName());
        },
        $locator->locateIdentifiersByType(
            new FunctionReflector($locator),
            new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION)
        )
    ));
};

$allFunctionNames = $declaredFunctionNames();

$functionExists = function (string $functionName) use ($allFunctionNames) : bool {
    return in_array(strtolower($functionName), $allFunctionNames, true);
};

class ReplaceUnqualifiedFunctionCallsWithQualifiedReferences implements NodeVisitor
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
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null:      $node stays as-is
     *  * false:     $node is removed from the parent array
     *  * array:     The return value is merged into the parent array (at the position of the $node)
     *  * otherwise: $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return null|Node|false|Node[] Node
     */
    public function leaveNode(Node $node) : ?Node
    {
        if ($node instanceof Namespace_) {
            $this->currentNamespace      = null;
            $this->importedFunctionNames = [];
        }

        // @todo name may be qualified, but imported...
        // @todo just wrap the name resolver first

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
        $currentNamespaceNamespacedName = $this->currentNamespace . '\\' . $originalNameString;

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

$ast = (new ParserFactory())
    ->create(ParserFactory::ONLY_PHP7)
    ->parse(file_get_contents(__DIR__ . '/test-asset/mixed-function-references.php'));

$traverser = new NodeTraverser();

$traverser->addVisitor(new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences($functionExists));

$modifiedAst = $traverser->traverse($ast);

echo(new Standard())->prettyPrint($modifiedAst);

