<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\UserWithIdStrategy;
use PHPUnit\Framework\TestCase;

class UserWithIdStrategyTest extends TestCase
{
    public function testWithUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $constraints = [];

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(123);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $config = $this->createMock(Config::class);

        $strategy = new UserWithIdStrategy($config);

        $this->assertTrue($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithInvalidUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $constraints = [];

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(789);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $config = $this->createMock(Config::class);

        $strategy = new UserWithIdStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }

    public function testWithoutUserId()
    {
        $params = [
            'userIds' => '123,456',
        ];

        $constraints = [];

        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn(null);

        $config = $this->createMock(Config::class);

        $strategy = new UserWithIdStrategy($config);

        $this->assertFalse($strategy->isEnabled($params, $constraints, $request));
    }
}
