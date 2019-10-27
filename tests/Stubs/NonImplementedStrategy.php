<?php

namespace MikeFrancis\LaravelUnleash\Tests\Stubs;

class NonImplementedStrategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        return true;
    }
}
