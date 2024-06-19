<?php

namespace HighLiuk\WordPressCommand;

use InvalidArgumentException;

/**
 * Utilities that do NOT depend on WordPress code.
 *
 * @see https://github.com/wp-cli/wp-cli/blob/main/php/utils.php
 */
class Utils
{
    /**
     * Regular expression pattern to match __FILE__ and __DIR__ constants.
     *
     * We try to be smart and only replace the constants when they are not within
     * quotes. Regular expressions being stateless, this is probably not 100% correct
     * for edge cases.
     *
     * @see https://regex101.com/r/9hXp5d/11
     * @see https://stackoverflow.com/a/171499/933065
     */
    const FILE_DIR_PATTERN = '%(?>#.*?$)|(?>//.*?$)|(?>/\*.*?\*/)|(?>\'(?:(?=(\\\\?))\1.)*?\')|(?>"(?:(?=(\\\\?))\2.)*?")|(?<file>\b__FILE__\b)|(?<dir>\b__DIR__\b)%ms';

    /**
     * Regular expression pattern to match the require call to wp-blog-header.php in
     * index.php.
     */
    const WP_BLOG_HEADER_PATTERN = '|^\s*require\s*\(?\s*(.+?)/wp-blog-header\.php([\'"])|m';

    /**
     * Find the directory that contains the WordPress files. Defaults to the current
     * working dir.
     */
    public function findWpRoot(): ?string
    {
        $dir = getcwd();

        if (! $dir) {
            return null;
        }

        while (is_readable($dir)) {
            if (file_exists("$dir/wp-load.php")) {
                return $dir;
            }

            if (file_exists("$dir/index.php")) {
                $path = $this->extractSubdirPath("$dir/index.php");
                if ($path) {
                    return $path;
                }
            }

            $parent_dir = dirname($dir);
            if (! $parent_dir || $parent_dir === $dir) {
                break;
            }
            $dir = $parent_dir;
        }

        return getcwd() ?: null;
    }

    /**
     * Attempts to find the path to the WP installation inside index.php
     */
    protected function extractSubdirPath(string $index_path): ?string
    {
        $index_code = file_get_contents($index_path);

        if (! $index_code) {
            return null;
        }

        if (! preg_match(static::WP_BLOG_HEADER_PATTERN, $index_code, $matches)) {
            return null;
        }

        $wp_path_src = $matches[1].$matches[2];
        $wp_path_src = $this->replacePathConsts($wp_path_src, $index_path);

        $wp_path = eval("return $wp_path_src;");

        if (! $this->isPathAbsolute($wp_path)) {
            $wp_path = dirname($index_path)."/$wp_path";
        }

        return $wp_path;
    }

    /**
     * Replace magic constants in some PHP source code.
     *
     * Replaces the __FILE__ and __DIR__ magic constants with the values they are
     * supposed to represent at runtime.
     */
    protected function replacePathConsts(string $source, string $path): string
    {
        // Solve issue with Windows allowing single quotes in account names.
        $file = addslashes($path);

        if (file_exists($file)) {
            $file = realpath($file);
        }

        if (! $file) {
            throw new InvalidArgumentException("Cannot find file: $path");
        }

        $dir = dirname($file);

        // Replace __FILE__ and __DIR__ constants with value of $file or $dir.
        $result = preg_replace_callback(
            static::FILE_DIR_PATTERN,
            static function ($matches) use ($file, $dir) {
                if (! empty($matches['file'])) {
                    return "'{$file}'";
                }

                if (! empty($matches['dir'])) {
                    return "'{$dir}'";
                }

                return $matches[0];
            },
            $source
        );

        if (! $result) {
            throw new InvalidArgumentException('Error replacing path constants');
        }

        return $result;
    }

    /**
     * Check if a path is absolute.
     */
    protected function isPathAbsolute(string $path): bool
    {
        // Windows.
        if (isset($path[1]) && $path[1] === ':') {
            return true;
        }

        return isset($path[0]) && $path[0] === '/';
    }

    /**
     * Set the URL of the WordPress site.
     */
    public function setUrl(string $url): void
    {
        $url = trim($url);

        if (! parse_url($url, PHP_URL_SCHEME)) {
            $url = "http://$url";
        }

        $url_data = parse_url($url);

        if ($url_data) {
            $_SERVER['HTTP_HOST'] = $url_data['host'] ?? null;
            $_SERVER['REQUEST_URI'] = $url_data['path'] ?? null;
        }
    }
}
