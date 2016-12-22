<?php

namespace Roave\FunctionFQNReplacer\Console\Command;

use BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter\Standard;
use Roave\FunctionFQNReplacer\Iterator\PhpFilesInDirectoryIterator;
use Roave\FunctionFQNReplacer\Lookup\FunctionExists;
use Roave\FunctionFQNReplacer\NodeVisitor\ReplaceUnqualifiedFunctionCallsWithQualifiedReferences;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ReplaceFunctionCalls extends Command
{
    /**
     * {@inheritDoc}
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    public function __construct()
    {
        parent::__construct('roave:function-fqn-replacer:replace');

        $this
            ->setDescription('Replaces relative function references with matching absolute function references')
            ->setDefinition([
                new InputArgument(
                    'path',
                    InputArgument::REQUIRED,
                    'Path to be checked for function usages to be replaced with absolute references'
                ),
                new InputArgument(
                    'function-definitions',
                    InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                    'Paths where functions were defined'
                )
            ]);
        $this->setHelp(<<<'EOT'
The <info>%command.name%</info> command replaces usages of functions
in your projects with their absolute references.

Example:

<comment>
namespace Foo {
    bar();
}
</comment>

Would become:

<comment>
namespace Foo {
    \bar();
}
</comment>

This obviously only when a definition for <comment>bar()</comment>
cannot be found in the <comment>Foo</comment> namespace.

Usage:

<info>%command.name% path/to/sources/to/change path/to/function/definitions</info>
<info>%command.name% path/to/sources/to/change path/to/function/definitions path/to/more/function/definitions</info>
EOT
        );
    }

    /**
     * {@inheritDoc}
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new ReplaceUnqualifiedFunctionCallsWithQualifiedReferences(new FunctionExists(
            new PhpInternalSourceLocator(),
            new DirectoriesSourceLocator($input->getArgument('function-definitions'))
        )));

        $parser  = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
        $printer = new Standard();

        foreach (PhpFilesInDirectoryIterator::iterate([$input->getArgument('path')]) as $file) {
            $output->writeln('<info>' . $file . '</info>');

            file_put_contents(
                $file,
                $printer->prettyPrint($traverser->traverse($parser->parse(file_get_contents($file))))
            );
        }
    }
}
