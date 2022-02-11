<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

class PayloadDefault extends Payload
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}