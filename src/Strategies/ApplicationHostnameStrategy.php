<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ApplicationHostnameStrategy extends Strategy
{
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        if (!parent::isEnabled($params, $constraints, $request)) {
            return false;
        }

        $hostNamesString = Arr::get($params, 'hostNames', '');

        $hostNames = explode(',', $hostNamesString);

        return $hostNamesString && in_array($request->getHost(), $hostNames);
    }
}
