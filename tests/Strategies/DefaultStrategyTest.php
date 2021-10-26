<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\DefaultStrategy;
use PHPUnit\Framework\TestCase;

class DefaultStrategyTest extends TestCase
{
    public function test()
    {
        $params = [];

        $constraints = [];

        $request = $this->createMock(Request::class);

        $config = $this->createMock(Config::class);

        $strategy = new DefaultStrategy($config);

        $this->assertTrue($strategy->isEnabled($params, $constraints, $request));
    }
}
