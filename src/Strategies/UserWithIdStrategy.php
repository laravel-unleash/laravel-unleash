<?php

namespace LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use LaravelUnleash\Strategies\Contracts\Strategy;

class UserWithIdStrategy implements Strategy
{
    public function isEnabled(array $params, Request $request): bool
    {
        $userIds = explode(',', Arr::get($params, 'userIds', ''));

        if (count($userIds) === 0 || !$user = $request->user()) {
            return false;
        }

        return in_array($user->getAuthIdentifier(), $userIds);
    }
}
