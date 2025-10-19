<?php

namespace App\Bootstrap;

class Container
{
    /**
     * @var array<string, array{factory: callable, shared: bool}>
     */
    private array $bindings = [];

    /**
     * @var array<string, object>
     */
    private array $instances = [];

    public function bind(string $abstract, callable $factory, bool $shared = false): void
    {
        $this->bindings[$abstract] = [
            'factory' => $factory,
            'shared' => $shared,
        ];
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bind($abstract, $factory, true);
    }

    public function instance(string $abstract, object $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    public function get(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (isset($this->bindings[$abstract])) {
            $object = ($this->bindings[$abstract]['factory'])($this);

            if ($this->bindings[$abstract]['shared']) {
                $this->instances[$abstract] = $object;
            }

            return $object;
        }

        return $this->build($abstract);
    }

    public function call(callable $callable, array $parameters = []): mixed
    {
        $reflection = $this->reflectCallable($callable);
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $parameters)) {
                $arguments[] = $parameters[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type !== null && !$type->isBuiltin()) {
                $arguments[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(sprintf(
                'Unable to resolve parameter %s for %s',
                $parameter->getName(),
                $reflection->getName()
            ));
        }

        return $callable(...$arguments);
    }

    private function build(string $class): object
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Class %s does not exist.', $class));
        }

        $reflection = new \ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \InvalidArgumentException(sprintf('Class %s is not instantiable.', $class));
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type !== null && !$type->isBuiltin()) {
                $dependencies[] = $this->get($type->getName());
                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                $dependencies[] = $parameter->getDefaultValue();
                continue;
            }

            throw new \RuntimeException(sprintf(
                'Unable to resolve constructor parameter %s for %s',
                $parameter->getName(),
                $class
            ));
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    private function reflectCallable(callable $callable): \ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        if (is_string($callable) && str_contains($callable, '::')) {
            return new \ReflectionMethod(...explode('::', $callable, 2));
        }

        return new \ReflectionFunction(\Closure::fromCallable($callable));
    }
}
