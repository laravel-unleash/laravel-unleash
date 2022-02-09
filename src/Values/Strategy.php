<?php

namespace MikeFrancis\LaravelUnleash\Values;

class Strategy
{
    protected $name;
    protected $parameters;

    public function __construct(string $name, ?array $parameters = null)
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function __isset($name)
    {
        return isset($this->{$name});
    }

    public function __get($name)
    {
        return $this->{$name};
    }
}