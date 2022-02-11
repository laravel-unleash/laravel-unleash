<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use MikeFrancis\LaravelUnleash\Unleash\Context;

class ContextTest extends \Orchestra\Testbench\TestCase
{
    use MockClient;

    public function testValues()
    {
        $sessionMock = $this->createMock(Store::class);
        $sessionMock->expects($this->once())->method('getId')->willReturn('test_session_id');
        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->willReturn($userMock);
        $request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');
        $request->expects($this->once())
            ->method('session')
            ->willReturn($sessionMock);

        $context = new Context($request);

        $this->assertEquals('test_session_id', $context->getSessionId());
        $this->assertEquals('127.0.0.1', $context->getIpAddress());
        $this->assertEquals('1', $context->getUserId());
        $this->assertEquals('Laravel', $context->getAppName());
        $this->assertEquals('testing', $context->getEnvironment());
    }

    public function testGetContextValue()
    {
        $sessionMock = $this->createMock(Store::class);
        $sessionMock->expects($this->once())->method('getId')->willReturn('test_session_id');
        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->willReturn($userMock);
        $request->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');
        $request->expects($this->once())
            ->method('session')
            ->willReturn($sessionMock);

        $context = new Context($request);

        $this->assertEquals('test_session_id', $context->getContextValue('sessionId'));
        $this->assertEquals('127.0.0.1', $context->getContextValue('ipAddress'));
        $this->assertEquals('1', $context->getContextValue('userId'));
        $this->assertEquals('Laravel', $context->getContextValue('appName'));
        $this->assertEquals('testing', $context->getContextValue('environment'));
    }

    public function testCustomerProperties()
    {
        $request = $this->createMock(Request::class);
        $request->expects($this->once())
            ->method('user')
            ->willReturn(null);
        $request->expects($this->once())
            ->method('getClientIp')
            ->willReturn(null);
        $request->expects($this->once())
            ->method('session')
            ->willReturn(null);

        $context = new Context($request);
        $context->foo = 'bar';

        $this->assertEquals('bar', $context->getContextValue('foo'));
        $this->assertEquals('bar', $context->foo);
        $this->assertTrue(isset($context->foo));
        unset($context->foo);
        $this->assertFalse(isset($context->foo));


        $this->assertEquals(null, $context->getContextValue('non_existant'));
        $this->assertEquals(null, $context->non_existant);
        $this->assertFalse(isset($context->non_existant));
    }
}