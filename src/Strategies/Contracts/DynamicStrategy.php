<?php

namespace MikeFrancis\LaravelUnleash\Strategies\Contracts;

use Illuminate\Http\Request;

interface DynamicStrategy
{
    /**
     * @param array $params Strategy Configuration from Unleash
     * @param Request $request Current Request
     * @param mixed $args An arbitrary number of arguments passed to FeatureFlag::enabled/disabled
     * @return bool
     */
    public function isEnabled(array $params, Request $request, ...$args): bool;
}
