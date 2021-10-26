<?php

namespace MikeFrancis\LaravelUnleash\Strategies\Contracts;

use Exception;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Constraints\Contracts\ConstraintHelper;

abstract class DynamicStrategy
{
    use ConstraintHelper;

    /**
     * @param array $params Strategy Configuration from Unleash
     * @param array $constraints Constraints from Unleash
     * @param Request $request Current Request
     * @param mixed $args An arbitrary number of arguments passed to isFeatureEnabled/Disabled
     * @return bool
     * @throws Exception
     */
    public function isEnabled(array $params, array $constraints, Request $request, ...$args): bool
    {
        return $this->validateConstraints($constraints);
    }
}
