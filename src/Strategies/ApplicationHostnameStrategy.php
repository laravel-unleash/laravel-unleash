<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ApplicationHostnameStrategy implements Strategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        $applicationHostnames = explode(',', Arr::get($params, 'applicationHostname', ''));

        if (count($applicationHostnames) === 0) {
            return false;
        }

        return in_array($request->getHost(), $applicationHostnames);
    }
}
