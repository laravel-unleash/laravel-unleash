<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ApplicationHostnameStrategy implements Strategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        $hostNamesString = Arr::get($params, 'hostNames');

        if (!$hostNamesString || !Str::contains($hostNamesString, ',')) {
            return false;
        }

        $hostNames = explode(',', $hostNamesString);

        return in_array($request->getHost(), $hostNames);
    }
}
