<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

class Override
{
    protected $contextName;
    protected $values;

    public function __construct(string $contextName, array $values)
    {
        $this->contextName = $contextName;
        $this->values = $values;
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