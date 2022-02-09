<?php

namespace MikeFrancis\LaravelUnleash\Tests\Middleware;

use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Facades\Unleash;
use MikeFrancis\LaravelUnleash\Middleware\FeatureDisabled;
use MikeFrancis\LaravelUnleash\ServiceProvider;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FeatureDisabledTest extends TestCase
{
    public function testFeatureIsDisabled()
    {
        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('inactive-flag', false),
        ]));

        $request = Request::create('/', 'GET');

        $middleware = new FeatureDisabled();
        $middleware->handle($request, function (Request $request) {
            $this->assertTrue(true);
        }, 'inactive-flag');
    }

    public function testFeatureIsEnabled()
    {
        $this->expectException(NotFoundHttpException::class);

        Unleash::fake(new FeatureFlagCollection([
            new FeatureFlag('active-flag', true),
        ]));

        $request = Request::create('/', 'GET');

        $middleware = new FeatureDisabled();
        $middleware->handle($request, function (Request $request) {
            // should not run
            $this->assertTrue(false);
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
