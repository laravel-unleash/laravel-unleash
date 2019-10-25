<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class RemoteAddressStrategy implements Strategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    $remoteAddresses = explode(',', Arr::get($params, 'remoteAddress', ''));

    if (count($remoteAddresses) === 0) {
      return false;
    }

    return in_array($request->ip(), $remoteAddresses);
  }
}
