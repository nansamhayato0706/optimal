<?php

declare(strict_types=1);

namespace App\Support;

final class Container
{
    private $bindings = [];
    private $singletons = [];
    private $resolved = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->singletons[$abstract] = $factory;
    }

    public function get(string $abstract)
    {
        if (isset($this->resolved[$abstract])) {
            return $this->resolved[$abstract];
        }

        if (isset($this->singletons[$abstract])) {
            $instance = ($this->singletons[$abstract])($this);
            $this->resolved[$abstract] = $instance;
            return $instance;
        }

        if (isset($this->bindings[$abstract])) {
            return ($this->bindings[$abstract])($this);
        }

        throw new \RuntimeException("No binding found for [{$abstract}].");
    }
}
