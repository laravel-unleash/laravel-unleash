<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\ApplicationHostnameStrategy;
use PHPUnit\Framework\TestCase;

class ApplicationHostnameStrategyTest extends TestCase
{
    public function testWithApplicationHostname()
    {
        $params = [
            'applicationHostname' => 'example.com,hostname.com',
        ];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('example.com');

        $strategy = new ApplicationHostnameStrategy();

        $this->assertTrue($strategy->isEnabled($params, $request));
    }

    public function testWithInvalidApplicationHostname()
    {
        $params = [
            'applicationHostname' => 'example.com,hostname.com',
        ];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('somewhere.com');

        $strategy = new ApplicationHostnameStrategy();

        $this->assertFalse($strategy->isEnabled($params, $request));
    }

    public function testWithoutRemoteAddressParameters()
    {
        $params = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('getHost')->willReturn('example.com');

        $strategy = new ApplicationHostnameStrategy();

        $this->assertFalse($strategy->isEnabled($params, $request));
    }
}
