<?php

namespace MikeFrancis\LaravelUnleash\Strategies;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class UserWithIdStrategy extends Strategy
{
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        if (!parent::isEnabled($params, $constraints, $request)) {
            return false;
        }

        $userIds = explode(',', Arr::get($params, 'userIds', ''));

        if (count($userIds) === 0 || !$user = $request->user()) {
            return false;
        }

        return in_array($user->getAuthIdentifier(), $userIds);
    }
}
