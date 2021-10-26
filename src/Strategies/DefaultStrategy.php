<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class DefaultStrategy extends Strategy
{
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        return parent::isEnabled($params, $constraints, $request);
    }
}
