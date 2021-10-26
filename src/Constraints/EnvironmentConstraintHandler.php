<?php

namespace MikeFrancis\LaravelUnleash\Constraints;

use Exception;

class EnvironmentConstraintHandler extends Contracts\ConstraintHandler
{
    /**
     * @throws Exception
     */
    public function validateConstraint(string $operator, array $values): bool
    {
        if (!in_array($operator, Operators::ALL_OPERATORS)) {
            throw new Exception('Operator ' . $operator . ' is not one of ' . implode(',', Operators::ALL_OPERATORS));
        }

        $environment = $this->config->get('unleash.environment');
        $isInEnvironment = in_array($environment, $values);

        if ($operator == Operators::IN) {
            return $isInEnvironment;
        } else {
            return !$isInEnvironment;
        }
    }
}