<?php

namespace MikeFrancis\LaravelUnleash\Tests\Constraints;

use Exception;
use Illuminate\Contracts\Config\Repository as Config;
use MikeFrancis\LaravelUnleash\Constraints\EnvironmentConstraintHandler;
use PHPUnit\Framework\TestCase;

class EnvironmentConstraintHandlerTest extends TestCase
{
    public function testInSuccessful()
    {
        $operator = 'IN';
        $values = ['testing'];

        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(1))->method('get')
            ->with('unleash.environment')
            ->willReturn('testing');

        $constraintHandler = new EnvironmentConstraintHandler($config);

        $this->assertTrue($constraintHandler->validateConstraint($operator, $values));
    }

    public function testInUnsuccessful()
    {
        $operator = 'IN';
        $values = ['not_testing'];

        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(1))->method('get')
            ->with('unleash.environment')
            ->willReturn('testing');

        $constraintHandler = new EnvironmentConstraintHandler($config);

        $this->assertFalse($constraintHandler->validateConstraint($operator, $values));
    }

    public function testNotInSuccessful()
    {
        $operator = 'NOT_IN';
        $values = ['not_testing'];

        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(1))->method('get')
            ->with('unleash.environment')
            ->willReturn('testing');

        $constraintHandler = new EnvironmentConstraintHandler($config);

        $this->assertTrue($constraintHandler->validateConstraint($operator, $values));
    }

    public function testNotInUnsuccessful()
    {
        $operator = 'NOT_IN';
        $values = ['testing'];

        $config = $this->createMock(Config::class);
        $config->expects($this->exactly(1))->method('get')
            ->with('unleash.environment')
            ->willReturn('testing');

        $constraintHandler = new EnvironmentConstraintHandler($config);

        $this->assertFalse($constraintHandler->validateConstraint($operator, $values));
    }

    public function testInvalidOperator()
    {
        $operator = 'MAYBE_IN';
        $values = ['testing'];

        $config = $this->createMock(Config::class);

        $constraintHandler = new EnvironmentConstraintHandler($config);

        $this->expectException(Exception::class);

        $constraintHandler->validateConstraint($operator, $values);
    }
}
