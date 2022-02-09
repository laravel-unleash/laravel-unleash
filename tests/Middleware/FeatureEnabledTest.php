<?php

namespace MikeFrancis\LaravelUnleash\Tests\Middleware;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Facades\Unleash;
use MikeFrancis\LaravelUnleash\Middleware\FeatureEnabled;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeatureEnabledTest extends TestCase
{
    public function testFeatureIsDisabled()
    {
        $this->expectException(NotFoundHttpException::class);

        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('inactive-flag', false),
        ]));

        $request = Request::create('/', 'GET');

        $middleware = new FeatureEnabled();
        $middleware->handle($request, function (Request $request) {
            // should not run
            $this->assertTrue(false);
        }, 'inactive-flag');
    }

    public function testFeatureIsEnabled()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
        ]));

        $request = Request::create('/', 'GET');

        $middleware = new FeatureEnabled();
        $middleware->handle($request, function (Request $request) {
            $this->assertTrue(true);
        }, 'active-flag');
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
