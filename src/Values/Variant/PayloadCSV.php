<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

use MikeFrancis\LaravelUnleash\ReadonlyArray;

class PayloadCSV extends Payload implements \ArrayAccess
{
    use ReadonlyArray;

    protected $values;

    public function __construct(string $value)
    {
        $this->values = str_getcsv($value);
    }

    public function __isset($name)
    {
        return isset($this->{$name});
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function offsetExists($offset)
    {
        return isset($this->values[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->values[$offset];
    }
}