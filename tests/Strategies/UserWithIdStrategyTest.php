<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\UserWithIdStrategy;
use Orchestra\Testbench\TestCase;

class UserWithIdStrategyTest extends TestCase
{
    public function testWithUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(123);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $strategy = new UserWithIdStrategy();

        $this->assertTrue($strategy->isEnabled($params, $request));
    }

    public function testWithInvalidUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(789);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled($params, $request));
    }

    public function testWithoutUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn(null);

        $strategy = new UserWithIdStrategy();

        $this->assertFalse($strategy->isEnabled($params, $request));
    }
}
