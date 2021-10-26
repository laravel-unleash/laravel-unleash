<?php

namespace MikeFrancis\LaravelUnleash\Strategies\Contracts;

use Exception;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Constraints\Contracts\ConstraintHelper;

abstract class Strategy
{
    use ConstraintHelper;

    /**
     * @param array $params Strategy Configuration from Unleash
     * @param array $constraints Constraints from Unleash
     * @param Request $request Current Request
     * @return bool
     * @throws Exception
     */
    public function isEnabled(array $params, array $constraints, Request $request): bool
    {
        return $this->validateConstraints($constraints);
    }
}
