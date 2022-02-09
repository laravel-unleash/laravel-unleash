<?php

namespace MikeFrancis\LaravelUnleash\Values;

use Illuminate\Support\Collection;
use MikeFrancis\LaravelUnleash\Values\Variant\Override;
use MikeFrancis\LaravelUnleash\Values\Variant\Payload;

class Variant
{
    protected $name;
    protected $weight;
    protected $weightType;
    protected $stickiness;
    protected $payload;
    protected $overrides;

    public function __construct($name, $weight, $weightType, $stickiness, $payload, $overrides)
    {
        $this->name = $name;
        $this->weight = $weight;
        $this->weightType = $weightType;
        $this->stickiness = $stickiness;
        $this->payload = Payload::factory($payload['type'], $payload['value']);
        $this->overrides = Collection::wrap($overrides)->map(function ($item, $key) {
            return new Override($item['contextName'], $item['values']);
        });
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