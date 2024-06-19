<?php

namespace HighLiuk\WordPressCommand;

use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * The WordPress CLI application.
 */
class WordPressApplication extends Application
{
    /**
     * The instance of the singleton.
     */
    protected static ?self $instance = null;

    /**
     * Create a new WordPress CLI application.
     */
    protected function __construct(public readonly Utils $utils)
    {
        parent::__construct('WordPress Command');
    }

    /**
     * Get the instance of the WordPress CLI application.
     */
    public static function getInstance(): self
    {
        return self::$instance ??= new self(new Utils());
    }

    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        // Add the --url option to the application
        $this->setDefaultParameters();

        // Get the URL from the --url option and set the current WordPress URL
        $url = (string) $input->getParameterOption(['--url']);
        $this->setUrl($url);

        // Load the WordPress environment
        $this->loadWordPress();

        // Run the application
        return parent::doRun($input, $output);
    }

    /**
     * Register the --url option to the application.
     */
    protected function setDefaultParameters(): void
    {
        $option = new InputOption('url', null, InputOption::VALUE_REQUIRED, 'The URL of the WordPress site');

        $this->getDefinition()->addOption($option);
    }

    /**
     * Set the URL of the WordPress site.
     */
    protected function setUrl(string $url): void
    {
        $this->utils->setUrl($url);
    }

    /**
     * Load the WordPress environment.
     */
    protected function loadWordPress(): void
    {
        $wp_root = $this->utils->findWpRoot();

        if (! $wp_root) {
            throw new RuntimeException('Cannot find the WordPress root directory.');
        }

        require_once "$wp_root/wp-load.php";
    }
}
