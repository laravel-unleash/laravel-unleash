<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\ApplicationHostnameStrategy;
use PHPUnit\Framework\TestCase;

class ApplicationHostnameStrategyTest extends TestCase
{

    public function testWithSingleApplicationHostname()
    {
        $params = [
            'hostNames' => 'example.com',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('example.com');

        $config = $this->createMock(Config::class);

        $strategy = new ApplicationHostnameStrategy($config);

        $this->assertTrue($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithApplicationHostname()
    {
        $params = [
            'hostNames' => 'example.com,hostname.com',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('example.com');

        $config = $this->createMock(Config::class);

        $strategy = new ApplicationHostnameStrategy($config);

        $this->assertTrue($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithInvalidApplicationHostname()
    {
        $params = [
            'hostNames' => 'example.com,hostname.com',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('somewhere.com');

        $config = $this->createMock(Config::class);

        $strategy = new ApplicationHostnameStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithoutRemoteAddressParameters()
    {
        $params = [];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method('getHost');

        $config = $this->createMock(Config::class);

        $strategy = new ApplicationHostnameStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithEmptyRemoteAddressParameters()
    {
        $params = [
            'hostNames' => '',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->never())->method('getHost');

        $config = $this->createMock(Config::class);

        $strategy = new ApplicationHostnameStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }
}
