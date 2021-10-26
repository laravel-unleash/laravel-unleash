<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\RemoteAddressStrategy;
use PHPUnit\Framework\TestCase;

class RemoteAddressStrategyTest extends TestCase
{
    public function testWithRemoteAddress()
    {
        $params = [
            'remoteAddress' => '1.1.1.1,2.2.2.2',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('ip')->willReturn('1.1.1.1');

        $config = $this->createMock(Config::class);

        $strategy = new RemoteAddressStrategy($config);

        $this->assertTrue($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithInvalidRemoteAddress()
    {
        $params = [
            'remoteAddress' => '1.1.1.1,2.2.2.2',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('ip')->willReturn('1.1.1.2');

        $config = $this->createMock(Config::class);

        $strategy = new RemoteAddressStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithoutRequestIP()
    {
        $params = [
            'remoteAddress' => '1.1.1.1,2.2.2.2',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('ip')->willReturn(null);

        $config = $this->createMock(Config::class);

        $strategy = new RemoteAddressStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithoutRemoteAddressParameters()
    {
        $params = [];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method('ip');

        $config = $this->createMock(Config::class);

        $strategy = new RemoteAddressStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }
}
