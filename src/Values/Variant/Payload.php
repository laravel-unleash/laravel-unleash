<?php

namespace MikeFrancis\LaravelUnleash\Values\Variant;

use MikeFrancis\LaravelUnleash\Exception\UnknownVariantTypeException;

class Payload
{
    public static function factory($type, $value)
    {
        switch ($type) {
            case "string":
                return new PayloadString($value);
            case "json":
                return new PayloadJSON($value);
            case "csv":
                return new PayloadCSV($value);
            case "default":
                return new PayloadDefault($value);
        }

        throw new UnknownVariantTypeException(sprintf("Unknown variant type: %s", $type));
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