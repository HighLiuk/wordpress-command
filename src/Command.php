<?php

namespace HighLiuk\WordPressCommand;

use HighLiuk\WordPressCommand\Traits\InfersMetadata;
use HighLiuk\WordPressCommand\Traits\PrintsLines;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Base class for commands.
 */
class Command extends BaseCommand
{
    use InfersMetadata;
    use PrintsLines;

    /**
     * The input instance.
     */
    protected InputInterface $input;

    /**
     * The output instance.
     */
    protected OutputInterface $output;

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = '';

    /**
     * The command namespace.
     *
     * @var string
     */
    protected $namespace = '';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = '';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        try {
            $this->{'handle'}(...$this->getHandleArgs());
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Resolve the arguments and options for the handle method.
     *
     * @return mixed[]
     */
    private function getHandleArgs(): array
    {
        $reflection = new ReflectionMethod($this, 'handle');
        $parameters = $reflection->getParameters();
        $args = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            $name = str_replace('_', '-', $name);

            // resolve the argument value
            if ($this->input->hasArgument($name)) {
                $arg = $this->input->getArgument($name);
            } elseif ($this->input->hasOption($name)) {
                $arg = $this->input->getOption($name);
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arg = $parameter->getDefaultValue();
            } else {
                $arg = null;
            }

            // resolve the argument type
            if ($parameter->hasType()) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                    settype($arg, $type->getName());
                }
            }

            $args[] = $arg;
        }

        return $args;
    }
}
