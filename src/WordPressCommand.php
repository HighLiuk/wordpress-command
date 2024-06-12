<?php

namespace HighLiuk\WordPressCommand;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * Base class for WordPress commands.
 */
class WordPressCommand extends Command
{
    /**
     * The input instance.
     */
    protected InputInterface $input;

    /**
     * The output instance.
     */
    protected OutputInterface $output;

    /**
     * Blog IDs to skip.
     *
     * @var int[]
     */
    private array $skip_blogs = [];

    /**
     * The command name.
     *
     * @var string
     */
    protected $name = '';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = '';

    protected function configure(): void
    {
        if ($name = $this->name ?: $this->inferCommandName()) {
            $this->setName($name);
        }

        if ($description = $this->description ?: $this->inferCommandDescription()) {
            $this->setDescription($description);
        }

        $this
            ->addOption('blogs', 'b', InputOption::VALUE_OPTIONAL, 'The IDs of the blogs (comma-separated)', '1')
            ->setup();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        foreach ($this->getBlogIds() as $blog_id) {
            switch_to_blog($blog_id);

            try {
                $this->{'handle'}(...$this->getHandleArgs());
            } catch (Throwable $e) {
                $this->output->writeln("<error>{$e->getMessage()}</error>");
            }
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

    /**
     * Infer the command name from the class name.
     */
    private function inferCommandName(): ?string
    {
        $reflection = new ReflectionClass($this);
        $className = $reflection->getShortName();

        $className = preg_replace('/([a-z])([A-Z])/', '$1-$2', $className);
        if ($className === null) {
            return null;
        }
        $className = strtolower($className);
        $className = preg_replace('/-/', ':', $className, 1);

        return $className;
    }

    /**
     * Infer the command description from the doc comment of the class.
     */
    private function inferCommandDescription(): ?string
    {
        $reflection = new ReflectionClass($this);

        if (! $docComment = $reflection->getDocComment()) {
            return null;
        }

        $description = preg_replace('/(^[\/\*\s]*|[\s\*\/]*$)/', '', $docComment);

        if ($description === null) {
            return null;
        }

        return trim($description);

    }

    /**
     * Get the blog IDs from the input.
     *
     * @return int[]
     */
    private function getBlogIds(): array
    {
        // if the --skip-blogs option is provided, skip the blogs with the provided IDs
        if ($this->input->hasOption('skip-blogs') && $this->input->getOption('skip-blogs')) {
            $skip_blogs = explode(',', (string) $this->input->getOption('skip-blogs'));
            $skip_blogs = array_map('intval', $skip_blogs);

            $this->skip_blogs = [...$this->skip_blogs, ...$skip_blogs];
        }

        // if the --all-sites option is provided, get all the blog IDs. Otherwise, get
        // the blog IDs from the --blogs option
        if ($this->input->hasOption('all-sites') && $this->input->getOption('all-sites')) {
            $blog_ids = get_sites([
                'fields' => 'ids',
                'archived' => 0,
                'deleted' => 0,
            ]);
        } else {
            $blog_ids = explode(',', (string) $this->input->getOption('blogs'));
        }

        // exclude any blog IDs from the skip list in the blog IDs list
        $blog_ids = array_map('intval', $blog_ids);
        $blog_ids = array_diff($blog_ids, $this->skip_blogs);
        $blog_ids = array_filter($blog_ids);
        $blog_ids = array_values($blog_ids);

        return $blog_ids;
    }

    /**
     * Allow to run the command on all sites by using the --all-sites option.
     *
     * @return $this
     */
    protected function allowToRunCommandOnAllSites(): static
    {
        return $this
            ->addOption('all-sites', null, InputOption::VALUE_NONE, 'Run the command on all sites')
            ->addOption('skip-blogs', null, InputOption::VALUE_OPTIONAL, 'The IDs of the blog(s) to skip (comma-separated)');
    }

    /**
     * Skips the command on the main site.
     */
    protected function skipCommandOnMainSite(): static
    {
        $this->skip_blogs[] = 1;

        return $this;
    }

    /**
     * Setup the command.
     */
    protected function setup(): void
    {
        //
    }

    /**
     * Outputs a message.
     */
    protected function line(string $message, bool $newLine = true): void
    {
        if ($newLine) {
            $this->output->writeln($message);
        } else {
            $this->output->write($message);
        }
    }

    /**
     * Outputs an info message.
     */
    protected function info(string $message, bool $newLine = true): void
    {
        $this->line("<question>$message</question>", $newLine);
    }

    /**
     * Outputs a success message.
     */
    protected function success(string $message, bool $newLine = true): void
    {
        $this->line("<info>$message</info>", $newLine);
    }

    /**
     * Outputs a warning message.
     */
    protected function warning(string $message, bool $newLine = true): void
    {
        $this->line("<comment>$message</comment>", $newLine);
    }

    /**
     * Outputs an error message.
     */
    protected function error(string $message, bool $newLine = true): void
    {
        $this->line("<error>$message</error>", $newLine);
    }
}
