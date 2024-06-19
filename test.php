<?php

trait Reflects
{
    private function reflection()
    {
        return new ReflectionClass($this);
    }
}

class User
{
    use Reflects;

    public function greet()
    {
        $reflection = $this->reflection();
        $className = $reflection->getShortName();
        echo "Hello, $className!\n";
    }
}

$user = new User();
$user->greet();
