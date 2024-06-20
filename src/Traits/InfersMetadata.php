<?php

namespace HighLiuk\WordPressCommand\Traits;

use ReflectionClass;

/**
 * Infers metadata from the class, like the command name and description.
 */
trait InfersMetadata
{
    /**
     * @var ReflectionClass<self>
     */
    private ReflectionClass $reflection;

    /**
     * Get the reflection class instance.
     *
     * @return ReflectionClass<self>
     */
    private function reflection(): ReflectionClass
    {
        return $this->reflection ??= new ReflectionClass($this);
    }

    /**
     * Get the name of the command without the namespace.
     */
    protected function getRawName(): ?string
    {
        return $this->name
            ?: parent::getName()
            ?: $this->inferName();
    }

    public function getName(): ?string
    {
        // get the namespace and the name without the namespace (raw name)
        $namespace = $this->namespace;
        $raw_name = $this->getRawName();

        // if the raw name is null, return null
        if ($raw_name === null) {
            return null;
        }

        // if the namespace is empty, return the raw name
        if (! $namespace) {
            return $raw_name;
        }

        // otherwise, both the namespace and the raw name are present. In this case, we
        // have to concatenate them with a colon. If the raw name already contains a
        // colon, we replace it with a dash first, so that the namespace is preserved.
        $raw_name = preg_replace('/:/', '-', $raw_name, 1);

        // return the namespace and the raw name concatenated with a colon
        return $namespace.':'.$raw_name;
    }

    public function getDescription(): string
    {
        return $this->description
            ?: parent::getDescription()
            ?: $this->inferDescription();
    }

    /**
     * Infer the command name from the class name.
     */
    private function inferName(): ?string
    {
        $className = $this->reflection()->getShortName();

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
    private function inferDescription(): string
    {
        if (! $docComment = $this->reflection()->getDocComment()) {
            return '';
        }

        // split into lines
        $lines = explode("\n", $docComment);
        // remove the first and last line
        array_shift($lines);
        array_pop($lines);
        // remove whitespaces and asterisks
        $lines = array_map(fn (string $line) => ltrim(rtrim($line), " *\n\r\t\v\0"), $lines);
        // remove empty lines / tags
        $lines = array_filter($lines, fn (string $line) => $line && $line[0] !== '@');
        // join the lines
        $description = implode(' ', $lines);

        return $description;
    }
}
