<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class RemoteAddressStrategy implements Strategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    $remoteAddresses = explode(',', array_get($params, 'remoteAddress', ''));

    if (count($remoteAddresses) === 0) {
      return false;
    }

    return in_array($request->ip(), $remoteAddresses);
  }
}
