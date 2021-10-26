<?php

namespace MikeFrancis\LaravelUnleash\Constraints\Contracts;

use Illuminate\Contracts\Config\Repository as Config;

abstract class ConstraintHandler
{
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    abstract public function validateConstraint(string $operator, array $values): bool;
}
