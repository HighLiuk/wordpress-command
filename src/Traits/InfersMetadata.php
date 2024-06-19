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

    public function getName(): ?string
    {
        return $this->name
            ?: parent::getName()
            ?: $this->inferName();
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
