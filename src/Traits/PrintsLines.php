<?php

namespace HighLiuk\WordPressCommand\Traits;

/**
 * Outputs messages to the console.
 */
trait PrintsLines
{
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
