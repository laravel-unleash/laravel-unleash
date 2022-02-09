<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

class PayloadString extends Payload
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}