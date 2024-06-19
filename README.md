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

## Features

### Auto Inference for Name and Description

By default, the command name is inferred from the class name. For instance, the `HelloBlog` command will be available as `hello:blog`. Similarly, the command description is inferred from the class docblock. If you want to customize the command name and description, you can use the `setName` and `setDescription` methods in the `setup` method (see [Customization](#customization)), or you can use the shorthand properties:

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

### Customization

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

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
