<?php

namespace MikeFrancis\LaravelUnleash\Tests\Facades;

use MikeFrancis\LaravelUnleash\Facades\Feature;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;

class FeatureTest extends TestCase
{
    public function testFacade()
    {
        Feature::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
            new FeatureFlag('inactive-flag', false),
        ]));

        $this->assertEquals(
            new FeatureFlagCollection([
                new FeatureFlag('active-flag', true),
                new FeatureFlag('inactive-flag', false),
            ]),
            Feature::all()
        );

        $this->assertTrue(Feature::enabled('active-flag'));
        $this->assertFalse(Feature::disabled('active-flag'));

        $this->assertTrue(Feature::disabled('inactive-flag'));
        $this->assertFalse(Feature::enabled('inactive-flag'));

        $this->assertTrue(Feature::disabled('unknown-flag'));
        $this->assertFalse(Feature::enabled('unknown-flag'));

        $this->assertEquals(Feature::all(), Feature::all());

        $this->assertEquals(Feature::get('active-flag'), Feature::get('active-flag'));
        $this->assertEquals(Feature::enabled('active-flag'), Feature::enabled('active-flag'));
        $this->assertEquals(Feature::disabled('active-flag'), Feature::disabled('active-flag'));

        $this->assertEquals(Feature::get('inactive-flag'), Feature::get('inactive-flag'));
        $this->assertEquals(Feature::enabled('inactive-flag'), Feature::enabled('inactive-flag'));
        $this->assertEquals(Feature::disabled('inactive-flag'), Feature::disabled('inactive-flag'));

        $this->assertEquals(Feature::get('unknown-flag'), Feature::get('unknown-flag'));
        $this->assertEquals(Feature::enabled('unknown-flag'), Feature::enabled('unknown-flag'));
        $this->assertEquals(Feature::disabled('unknown-flag'), Feature::disabled('unknown-flag'));
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}