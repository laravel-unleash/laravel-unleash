<?php

namespace MikeFrancis\LaravelUnleash\Tests\Stubs;

use Illuminate\Http\Request;

class NonImplementedStrategy
{
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        return true;
    }
}
