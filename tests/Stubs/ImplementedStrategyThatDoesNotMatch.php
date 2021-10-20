<?php

namespace MikeFrancis\LaravelUnleash\Tests\Stubs;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ImplementedStrategyThatDoesNotMatch implements Strategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        return false;
    }
}
