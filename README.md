# WordPress Command

An easy way to define custom commands in WordPress powered by Symfony Console instead of WP-CLI.

## Installation

Use composer to install the package:

```bash
composer require highliuk/wordpress-command
```

## Usage

First, create your custom command by extending the `WordPressCommand` class:

```php
use Highliuk\WordPressCommand\WordPressCommand;

/**
 * Greets the blog with its name.
 */
class HelloBlog extends WordPressCommand
{
    protected function handle(): void
    {
        $name = get_bloginfo('name');

        $this->line("Hello, $name!");
    }
}
```

Then, register your command in your WordPress code:

```php
use Highliuk\WordPressCommand\WordPressApplication;

$app = WordPressApplication::getInstance();
$app->add(new HelloBlog());
```

Now you can run your custom command:

```bash
vendor/bin/console hello:blog
# Hello, My Blog!
```

You have access to all of the Symfony Console features, such as options and arguments. See the [Symfony Console documentation](https://symfony.com/doc/current/components/console.html) for more information.

### Customization

You can customize the command by overriding the `setup` method:

```php
use Highliuk\WordPressCommand\WordPressCommand;

class HelloBlog extends WordPressCommand
{
    protected function setup(): void
    {
        $this->setName('greet:blog');
    }

    protected function handle(): void
    {
        $name = get_bloginfo('name');

        $this->line("Hello, $name!");
    }
}
```

By default, the command name is inferred from the class name. For instance, the `HelloBlog` command will be available as `hello:blog`. Similarly, the command description is inferred from the class docblock. If you want to customize the command name and description, you can use the `setName` and `setDescription` methods in the `setup` method, or you can use the shorthand properties:

```php
use Highliuk\WordPressCommand\WordPressCommand;

class HelloBlog extends WordPressCommand
{
    protected $name = 'greet:blog';
    protected $description = 'Greets the blog with its name.';

    protected function handle(): void
    {
        $name = get_bloginfo('name');

        $this->line("Hello, $name!");
    }
}
```

## Features

### Argument and Option Bindings

You can access arguments and options from your handle method:

```php
use Highliuk\WordPressCommand\WordPressCommand;
use Symfony\Component\Console\Input\InputArgument;

class GreetUser extends WordPressCommand
{
    protected function setup(): void
    {
        $this
            ->addArgument('user', InputArgument::REQUIRED, 'The user to greet')
            ->addOption('uppercase', 'u', 'Whether to uppercase the user name');
    }

    protected function handle(string $user, bool $uppercase): void
    {
        if ($uppercase) {
            $user = strtoupper($user);
        }

        $this->line("Hello, $user!");
    }
}
```

```bash
vendor/bin/console greet:user john -u
# Hello, JOHN!
```

### Multisite Support

By default, the command will run on the main site. If you want to run the command on specific sites, you can use the `--blogs` option:

```bash
vendor/bin/console hello:blog --blogs=1,2,3
```

Or you can run the command on all sites by using the `--all-sites` option:

```bash
vendor/bin/console hello:blog --all-sites
```

To use the `--all-sites` option, you explicitly need to enable it via the `allowToRunCommandOnAllSites` method:

```php
use Highliuk\WordPressCommand\WordPressCommand;

class HelloBlog extends WordPressCommand
{
    protected function setup(): void
    {
        $this->allowToRunCommandOnAllSites();
    }

    protected function handle(): void
    {
        $name = get_bloginfo('name');

        $this->line("Hello, $name!");
    }
}
```

If you want to exclude specific sites, you can use the `--skip-blogs` option:

```bash
vendor/bin/console hello:blog --all-sites --skip-blogs=1,2,3
```

If your command should **never** run on the main site, you can use the `skipCommandOnMainSite` method, so that the command will only run on subsites:

```php
use Highliuk\WordPressCommand\WordPressCommand;

class HelloBlog extends WordPressCommand
{
    protected function setup(): void
    {
        $this->skipCommandOnMainSite();
    }

    protected function handle(): void
    {
        $name = get_bloginfo('name');

        $this->line("Hello, $name!");
    }
}
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
