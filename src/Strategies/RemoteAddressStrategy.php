<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class RemoteAddressStrategy extends Strategy
{
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        if (!parent::isEnabled($params, $constraints, $request)) {
            return false;
        }

        $remoteAddressesString = Arr::get($params, 'remoteAddress', '');

        if (!$remoteAddressesString || !Str::contains($remoteAddressesString, ',')) {
            return false;
        }

        $remoteAddresses = explode(',', $remoteAddressesString);

        return in_array($request->ip(), $remoteAddresses);
    }
}
