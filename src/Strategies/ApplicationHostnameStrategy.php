<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class ApplicationHostnameStrategy implements Strategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    $applicationHostnames = explode(',', array_get($params, 'hostNames', ''));

    if (count($applicationHostnames) === 0) {
      return false;
    }

    return in_array($request->getHost(), $applicationHostnames);
  }
}
