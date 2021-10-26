<?php

namespace MikeFrancis\LaravelUnleash\Constraints;

class Operators
{
    public const IN = 'IN';
    public const NOT_IN = 'NOT_IN';

    public const ALL_OPERATORS = [self::IN, self::NOT_IN];
}
