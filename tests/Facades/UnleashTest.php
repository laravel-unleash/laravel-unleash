<?php

namespace MikeFrancis\LaravelUnleash\Tests\Facades;

use MikeFrancis\LaravelUnleash\Facades\Unleash;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;

class UnleashTest extends TestCase
{
    public function testMethodAliases()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Unleash::all()
        );

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertTrue(Unleash::disabled('unknown-flag'));
        $this->assertFalse(Unleash::enabled('unknown-flag'));

        $this->assertEquals(Unleash::all(), Unleash::all());

        $this->assertEquals(Unleash::get('active-flag'), Unleash::get('active-flag'));
        $this->assertEquals(Unleash::enabled('active-flag'), Unleash::enabled('active-flag'));
        $this->assertEquals(Unleash::disabled('active-flag'), Unleash::disabled('active-flag'));

        $this->assertEquals(Unleash::get('inactive-flag'), Unleash::get('inactive-flag'));
        $this->assertEquals(Unleash::enabled('inactive-flag'), Unleash::enabled('inactive-flag'));
        $this->assertEquals(Unleash::disabled('inactive-flag'), Unleash::disabled('inactive-flag'));

        $this->assertEquals(Unleash::get('unknown-flag'), Unleash::get('unknown-flag'));
        $this->assertEquals(Unleash::enabled('unknown-flag'), Unleash::enabled('unknown-flag'));
        $this->assertEquals(Unleash::disabled('unknown-flag'), Unleash::disabled('unknown-flag'));
    }

    public function testBasicMock()
    {
        Unleash::shouldReceive('enabled')->with('active')->andReturnTrue();
        $this->assertTrue(Unleash::enabled('active'));

        Unleash::shouldReceive('disabled')->with('inactive')->andReturnTrue();
        $this->assertTrue(Unleash::disabled('inactive'));
    }

    public function testEnabledFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true)
        ]));

        $this->assertTrue(Unleash::enabled('active-flag'));

        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertEquals(new FeatureFlag('active-flag', true), Unleash::get('active-flag'));

        $this->assertEquals([new FeatureFlag('active-flag', true)], Unleash::all()->all());
    }

    public function testDisabledFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('inactive-flag', false)
        ]));

        $this->assertTrue(Unleash::disabled('inactive-flag'));

        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertEquals(new FeatureFlag('inactive-flag', false), Unleash::get('inactive-flag'));

        $this->assertEquals([new FeatureFlag('inactive-flag', false)], Unleash::all()->all());
    }

    public function testMixedFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertFalse(Unleash::disabled('active-flag'));

        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertFalse(Unleash::enabled('inactive-flag'));

        $this->assertTrue(Unleash::disabled('unknown-flag'));
        $this->assertFalse(Unleash::enabled('unknown-flag'));
    }

    public function testEnabledWithArgsFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            (new FeatureFlag('active-flag', true))->withTestArgs('foo'),
            (new FeatureFlag('active-flag', true))->withTestArgs('foo', 'bar'),
        ]));

        $this->assertTrue(Unleash::enabled('active-flag', 'foo'));
        $this->assertFalse(Unleash::disabled('active-flag', 'foo'));

        $this->assertTrue(Unleash::enabled('active-flag', 'foo', 'bar'));
        $this->assertFalse(Unleash::disabled('active-flag', 'foo', 'bar'));

        $this->assertFalse(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::disabled('active-flag'));


        $this->assertFalse(Unleash::enabled('active-flag', 'foo', 'bar', 'baz'));
        $this->assertTrue(Unleash::disabled('active-flag', 'foo', 'bar', 'baz'));
    }

    public function testEnabledWithArgsUsingFake()
    {
        Unleash::fake(
            (new FeatureFlag('active-flag', true))->withTestArgsUsing(function (bool $arg) {
                return !$arg;
            })
        );

        $this->assertTrue(Unleash::enabled('active-flag', false));
        $this->assertFalse(Unleash::disabled('active-flag', false));

        $this->assertTrue(Unleash::disabled('active-flag', true));
        $this->assertFalse(Unleash::enabled('active-flag', true));
    }

    public function testEnabledNotFake()
    {
        $this->assertFalse(Unleash::enabled('unknown-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testDisabledNotFake()
    {
        $this->assertFalse(Unleash::enabled('unknown-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testGetWithFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(new FeatureFlag('active-flag', true), Unleash::get('active-flag'));

        $this->assertEquals(new FeatureFlag('inactive-flag', false), Unleash::get('inactive-flag'));
    }

    public function testGetWithArgsFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            (new FeatureFlag('active-flag', true))->withTestArgs('foo'),
            (new FeatureFlag('active-flag', false))->withTestArgs('foo', 'bar'),
            (new FeatureFlag('inactive-flag', false))->withTestArgs('foo'),
            (new FeatureFlag('inactive-flag', true))->withTestArgs('foo', 'bar'),
        ]));

        $this->assertEquals((new FeatureFlag('active-flag', true))->withTestArgs('foo'), Unleash::get('active-flag'));

        $this->assertEquals((new FeatureFlag('inactive-flag', false))->withTestArgs('foo'), Unleash::get('inactive-flag'));
    }

    public function testAllWithFake()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Unleash::all()
        );
    }

    public function testFakeAll()
    {
        Unleash::fake();
        $this->assertTrue(Unleash::disabled('active-flag'));
        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testFakeAllWithDefaultStatusTrue()
    {
        Unleash::fake()->withDefaultStatus(true);
        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::enabled('inactive-flag'));
        $this->assertTrue(Unleash::enabled('unknown-flag'));
    }

    public function testFakeAllWithDefaultStatusFalse()
    {
        Unleash::fake()->withDefaultStatus(false);
        $this->assertTrue(Unleash::disabled('active-flag'));
        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testFakeAllWithDefaultStatusUsing()
    {
        Unleash::fake()->withDefaultStatusUsing(function ($feature, $status, ... $args) {
            if (count($args) == 0) {
                return true;
            }
            return !$args[0];
        });

        $this->assertTrue(Unleash::enabled('active-flag', false));
        $this->assertTrue(Unleash::disabled('inactive-flag', true));
        $this->assertTrue(Unleash::enabled('unknown-flag'));
    }

    public function testFakeMixedWithArgsUsing()
    {
        Unleash::fake(
            (new FeatureFlag('active-flag', true))->withTestArgsUsing(function($arg) {
                return !$arg;
            })
        )->withDefaultStatus(false);

        $this->assertTrue(Unleash::enabled('active-flag', false));
        $this->assertTrue(Unleash::disabled('active-flag', true));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testFakeWithVariadicFeatureFlags()
    {
        Unleash::fake(
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false)
        );

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::disabled('inactive-flag'));
    }

    public function testFakeMultipleCalls()
    {
        Unleash::fake(new FeatureFlag('active-flag', true));
        Unleash::fake(new FeatureFlag('inactive-flag', false));

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testFakeWithArray()
    {
        Unleash::fake(['active-flag', 'another-flag']);

        $this->assertTrue(Unleash::enabled('active-flag'));
        $this->assertTrue(Unleash::enabled('active-flag', 'foo', 'bar'));
        $this->assertTrue(Unleash::enabled('another-flag'));
        $this->assertTrue(Unleash::enabled('another-flag', 'foo', 'bar'));
        $this->assertTrue(Unleash::disabled('inactive-flag'));
        $this->assertTrue(Unleash::disabled('unknown-flag'));
    }

    public function testAssertCalledFeatureEnabled()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Feature flag enabled called for someFlag 0 times, expected at least 1');

        $fake = Unleash::fake();
        $fake->assertCalledFeatureEnabled('someFlag');
        $fake->enabled('someFlag');
        $fake->assertCalledFeatureEnabled('someFlag');
    }

    public function testAssertCalledFeatureDisabled()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Feature flag disabled called for someFlag 0 times, expected at least 1');

        $fake = Unleash::fake();
        $fake->assertCalledFeatureDisabled('someFlag');
        $fake->disabled('someFlag');
        $fake->assertCalledFeatureDisabled('someFlag');
    }

    public function testAssertCalledFeatureGet()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Feature flag get called for someFlag 0 times, expected at least 1');

        $fake = Unleash::fake();
        $fake->assertCalledFeatureGet('someFlag');
        $fake->get('someFlag');
        $fake->assertCalledFeatureGet('someFlag');
    }

    public function testAssertCalledFeatureAll()
    {
        $this->expectException(ExpectationFailedException::class);
        $this->expectExceptionMessage('Get all feature flags called 0 times, expected at least 1');

        $fake = Unleash::fake();
        $fake->assertCalledFeatureAll();
        $fake->all();
        $fake->assertCalledFeatureAll();
    }

    public function testAssertCalledFeatureEnabledTimes()
    {
        $fake = Unleash::fake();
        $fake->assertNotCalledFeatureEnabled('someFlag');
        $fake->enabled('someFlag');
        $fake->enabled('someFlag');
        $fake->enabled('someFlag');
        $fake->assertCalledFeatureEnabled('someFlag');
        $fake->assertCalledFeatureEnabledTimes('someFlag', 3);
    }

    public function testAssertCalledFeatureDisabledTimes()
    {
        $fake = Unleash::fake();
        $fake->assertNotCalledFeatureDisabled('someFlag');
        $fake->disabled('someFlag');
        $fake->disabled('someFlag');
        $fake->disabled('someFlag');
        $fake->assertCalledFeatureDisabled('someFlag');
        $fake->assertCalledFeatureDisabledTimes('someFlag', 3);
    }

    public function testAssertCalledFeatureGetTimes()
    {
        $fake = Unleash::fake();
        $fake->assertNotCalledFeatureGet('someFlag');
        $fake->get('someFlag');
        $fake->get('someFlag');
        $fake->get('someFlag');
        $fake->assertCalledFeatureGet('someFlag');
        $fake->assertCalledFeatureGetTimes('someFlag', 3);
    }

    public function testAssertCalledFeatureAllTimes()
    {
        $fake = Unleash::fake();
        $fake->assertNotCalledFeatureAll();
        $fake->all();
        $fake->all();
        $fake->all();
        $fake->assertCalledFeatureAll();
        $fake->assertCalledFeatureAllTimes(3);
    }

    public function testAssertCalledFeatureEnabledNoSideEffects()
    {
        $fake = Unleash::fake();
        $fake->enabled('someFlag');
        $fake->assertNotCalledFeatureAll();
        $fake->assertNotCalledFeatureGet('someFlag');
        $fake->assertNotCalledFeatureDisabled('someFlag');
    }

    public function testAssertCalledFeatureDisabledNoSideEffects()
    {
        $fake = Unleash::fake();
        $fake->disabled('someFlag');
        $fake->assertNotCalledFeatureAll();
        $fake->assertNotCalledFeatureGet('someFlag');
        $fake->assertNotCalledFeatureEnabled('someFlag');
    }

    public function testAssertCalledFeatureGetNoSideEffects()
    {
        $fake = Unleash::fake();
        $fake->get('someFlag');
        $fake->assertNotCalledFeatureAll();
        $fake->assertNotCalledFeatureEnabled('someFlag');
        $fake->assertNotCalledFeatureDisabled('someFlag');
    }

    public function testAssertCalledFeatureAllNoSideEffects()
    {
        $fake = Unleash::fake();
        $fake->all();
        $fake->assertNotCalledFeatureGet('someFlag');
        $fake->assertNotCalledFeatureEnabled('someFlag');
        $fake->assertNotCalledFeatureDisabled('someFlag');
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    protected function tearDown(): void
    {
        Unleash::clearResolvedInstances();
    }
}