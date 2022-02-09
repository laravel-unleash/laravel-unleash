<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\DefaultStrategy;
use Orchestra\Testbench\TestCase;

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
