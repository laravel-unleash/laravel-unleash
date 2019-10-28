<?php

namespace MikeFrancis\LaravelUnleash\Tests\Stubs;

use Illuminate\Http\Request;

class NonImplementedStrategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        return true;
    }
}
