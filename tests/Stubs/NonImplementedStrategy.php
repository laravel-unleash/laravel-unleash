<?php

namespace LaravelUnleash\Tests\Stubs;

use Illuminate\Http\Request;

class NonImplementedStrategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        return true;
    }
}
