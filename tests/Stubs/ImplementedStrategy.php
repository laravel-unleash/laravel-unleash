<?php

namespace MikeFrancis\LaravelUnleash\Tests\Stubs;

use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ImplementedStrategy implements Strategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        return true;
    }
}
