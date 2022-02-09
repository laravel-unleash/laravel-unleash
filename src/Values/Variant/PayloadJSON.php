<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

use function GuzzleHttp\json_decode;

class PayloadJSON extends Payload
{
    protected $values;

    public function __construct(string $value)
    {
        $this->values = json_decode($value);
    }

    public function __isset($name)
    {
        return isset($this->values->{$name});
    }

    public function __get($name)
    {
        return $this->values->{$name};
    }
}