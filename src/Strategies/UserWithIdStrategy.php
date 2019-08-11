<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class UserWithIdStrategy implements Strategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    $userIds = explode(',', array_get($params, 'userIds', ''));

    if (count($userIds) === 0 || !$user = $request->user()) {
      return false;
    }

    return in_array($user->id(), $userIds);
  }
}
