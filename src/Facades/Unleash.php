<?php
namespace MikeFrancis\LaravelUnleash\Facades;

use Illuminate\Support\Facades\Facade;
use MikeFrancis\LaravelUnleash\Testing\Fakes\UnleashFake;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;

/**
 * @method static FeatureFlagCollection all()
 * @method static FeatureFlag get(string $name)
 * @method static bool enabled(string $feature, ... $args)
 * @method static bool disabled(string $feature, ... $args)
 * @method static FeatureFlagCollection getFeatures()
 * @method static FeatureFlag getFeature(string $name)
 * @method static bool isFeatureEnabled(string $feature, ... $args)
 * @method static bool isFeatureDisabled(string $feature, ... $args)
 */
class Unleash extends Facade
{
    static protected $fake;

    public static function fake(...$features): UnleashFake
    {
        if (static::getFacadeRoot() instanceof UnleashFake) {
            static::getFacadeRoot()->fake(... $features);
            return static::getFacadeRoot();
        }

        $fake = new UnleashFake(static::getFacadeRoot(), ... $features);
        static::swap($fake);
        return $fake;
    }

    protected static function getFacadeAccessor()
    {
        return 'unleash';
    }
}
