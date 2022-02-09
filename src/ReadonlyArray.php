<?php

namespace MikeFrancis\LaravelUnleash;

trait ReadonlyArray
{

    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException("Readonly array, unset not allowed");
    }

    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException("Readonly array, set not allowed");
    }
}