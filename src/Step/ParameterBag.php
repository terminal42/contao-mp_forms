<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Step;

class ParameterBag
{
    private array $parameters = [];

    public function __construct(array $parameters = [])
    {
        foreach ($parameters as $k => $v) {
            $this->set($k, $v);
        }
    }

    public function empty(): bool
    {
        return [] === $this->parameters;
    }

    public function clear(): void
    {
        $this->parameters = [];
    }

    public function all(): array
    {
        return $this->parameters;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    public function set(string $name, mixed $value): self
    {
        $this->parameters[$name] = $value;

        return $this;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->parameters);
    }

    public function remove(string $name): self
    {
        unset($this->parameters[$name]);

        return $this;
    }

    public function mergeWith(self $other): self
    {
        $clone = clone $this;

        $clone->parameters = array_replace_recursive($this->parameters, $other->parameters);

        return $clone;
    }

    public function equals(self $other): bool
    {
        return $this->all() === $other->all();
    }
}
