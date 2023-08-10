<?php

namespace LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use LaravelUnleash\Strategies\DefaultStrategy;
use PHPUnit\Framework\TestCase;
use stdClass;

class DefaultStrategyTest extends TestCase
{
    public function test()
    {
        $params = [];

        $request = $this->createMock(Request::class);

        $strategy = new DefaultStrategy();

        $this->assertTrue($strategy->isEnabled($params, $request));
    }
}
