<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\UserWithIdStrategy;
use PHPUnit\Framework\TestCase;
use stdClass;

class UserWithIdStrategyTest extends TestCase
{
  public function testWithUserId()
  {
    $params = [
      'userIds' => '123,456',
    ];

    $userMock = $this->createMock(Guard::class);
    $userMock->expects($this->once())->method('id')->willReturn(123);
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

    $userMock = $this->createMock(Guard::class);
    $userMock->expects($this->once())->method('id')->willReturn(789);
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
