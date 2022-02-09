<?php

namespace MikeFrancis\LaravelUnleash\Testing\Fakes;

use Illuminate\Support\Collection;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use PHPUnit\Framework\Assert as PHPUnit;

class UnleashFake
{
    /**
     * @var FeatureFlagCollection
     */
    protected $fakeFeatures;

    /**
     * @var Unleash
     */
    protected $unleash;

    protected $defaultStatus = false;

    protected $defaultStatusUsing;

    protected $calls;

    public function __construct($unleash, ... $features)
    {
        $this->unleash = $unleash;
        $this->fakeFeatures = $this->getFakeFeatures($features);

        $this->calls = [
            'enabled' => collect(),
            'disabled' => collect(),
            'get' => collect(),
            'all' => collect(),
        ];
    }

    public function enabled($name, ... $args)
    {
        $this->calls['enabled'][$name] = Collection::wrap($this->calls['enabled'][$name] ?? [])->add($name);
        return $this->_enabled($name, $args);
    }

    public function isFeatureEnabled($name, ... $args)
    {
        return $this->enabled($name, ... $args);
    }

    public function disabled($name, ... $args)
    {
        $this->calls['disabled'][$name] = Collection::wrap($this->calls['disabled'][$name] ?? [])->add($name);
        return !$this->_enabled($name, $args);
    }

    public function isFeatureDisabled($name, ... $args)
    {
        return $this->disabled($name, ... $args);
    }

    public function get($name)
    {
        $this->calls['get'][$name] = Collection::wrap($this->calls['get'][$name] ?? [])->add($name);
        return $this->getFakeFeature($name);
    }

    public function getFeature($name)
    {
        return $this->get($name);
    }

    public function all()
    {
        $this->calls['all']->add(true);

        $features = $this->unleash->getFeatures();

        $fakeFeatures = new Collection();
        $this->fakeFeatures->map(function($item) use ($fakeFeatures) {
            $name = $item->name;
            if (!$fakeFeatures->contains(function($item) use($name) {
                return $item->name === $name;
            })) {
                $fakeFeatures->push($item);
            }
        });

        return $features->merge($fakeFeatures);
    }

    public function getFeatures()
    {
        return $this->all();
    }

    public function fake(... $features)
    {
        $this->fakeFeatures = $this->fakeFeatures->concat($this->getFakeFeatures($features) ?? []);
    }

    public function withDefaultStatus(bool $status = false)
    {
        $this->defaultStatus = $status;
        return $this;
    }

    public function withDefaultStatusUsing(callable $callable)
    {
        $this->defaultStatusUsing = $callable;

        return $this;
    }

    public function assertCalledFeatureEnabled(string $name)
    {
        PHPUnit::assertTrue($this->calls['enabled']->has($name), sprintf('Feature flag enabled called for %s 0 times, expected at least 1', $name));
    }

    public function assertCalledFeatureDisabled(string $name)
    {
        PHPUnit::assertTrue($this->calls['disabled']->has($name), sprintf('Feature flag disabled called for %s 0 times, expected at least 1', $name));
    }

    public function assertCalledFeatureGet(string $name)
    {
        PHPUnit::assertTrue($this->calls['get']->has($name), sprintf('Feature flag get called for %s 0 times, expected at least 1', $name));
    }

    public function assertCalledFeatureAll()
    {
        PHPUnit::assertTrue($this->calls['all']->isNotEmpty(), 'Get all feature flags called 0 times, expected at least 1');
    }

    public function assertCalledFeatureEnabledTimes(string $name, int $times)
    {
        $calls = 0;
        if ($this->calls['enabled']->has($name)) {
            $calls = $this->calls['enabled'][$name]->count();
        }

        PHPUnit::assertEquals($times, $calls, sprintf('Feature flag enabled called for %s %d times, expected %d', $name, $calls, $times));
    }

    public function assertCalledFeatureDisabledTimes(string $name, int $times)
    {
        $calls = 0;
        if ($this->calls['disabled']->has($name)) {
            $calls = $this->calls['disabled'][$name]->count();
        }

        PHPUnit::assertEquals($times, $calls, sprintf('Feature flag disabled called for %s %d times, expected %d', $name, $calls, $times));
    }

    public function assertCalledFeatureGetTimes(string $name, int $times)
    {
        $calls = 0;
        if ($this->calls['get']->has($name)) {
            $calls = $this->calls['get'][$name]->count();
        }

        PHPUnit::assertEquals($times, $calls, sprintf('Feature flag get called for %s %d times, expected %d', $name, $calls, $times));
    }

    public function assertCalledFeatureAllTimes(int $times)
    {
        $calls = 0;
        $calls = $this->calls['all']->count();

        PHPUnit::assertEquals($times, $calls, sprintf('Get all feature flags called %d times, expected %d', $calls, $times));
    }

    public function assertNotCalledFeatureEnabled(string $name)
    {
        PHPUnit::assertFalse($this->calls['enabled']->has($name), sprintf('Feature flag enabled called for %s %d times, expected 0', $name, $this->calls['enabled']->get($name, Collection::empty())->count()));
    }

    public function assertNotCalledFeatureDisabled(string $name)
    {
        PHPUnit::assertFalse($this->calls['disabled']->has($name), sprintf('Feature flag disabled called for %s %d times, expected 0', $name, $this->calls['disabled']->get($name, Collection::empty())->count()));
    }

    public function assertNotCalledFeatureGet(string $name)
    {
        PHPUnit::assertFalse($this->calls['get']->has($name), sprintf('Feature flag get called for %s %d times, expected 0', $name, $this->calls['get']->get($name, Collection::empty())->count()));
    }

    public function assertNotCalledFeatureAll()
    {
        PHPUnit::assertTrue($this->calls['all']->isEmpty(), sprintf('Get all feature flags called %d times, expected 0', $this->calls['all']->count()));
    }

    public function __call($method, $args)
    {
        return $this->unleash->$method(... $args);
    }

    protected function getFakeFeature($feature, ?array $args = [])
    {
        if ($this->fakeFeatures->isEmpty()) {
            return new FeatureFlag($feature, $this->getDefaultStatus($feature, $this->defaultStatus, ... $args));
        }

        $featureFound = $this->fakeFeatures->first(function($item) use ($feature, $args) {
            if ($item->name === $feature) {
                $testUsing = $item->testArgsUsing;
                return $item->testArgs === $args || is_callable($testUsing);
            }
            return false;
        }, false);

        if ($featureFound !== false) {
            if (!is_callable($featureFound->testArgsUsing)) {
                return $featureFound;
            }
            $testUsing = $featureFound->testArgsUsing;
            if ($testUsing(... $args)) {
                return $featureFound;
            }
            $status = $this->defaultStatus;
            if (is_callable($this->defaultStatusUsing)) {
                $using = $this->defaultStatusUsing;
                $status = $using($feature, $this->defaultStatus, ... $args);
            }
            return new FeatureFlag($feature, $this->getDefaultStatus($feature, $status, ... $args));
        }

        $featureFound = $this->fakeFeatures->first(function($item) use ($feature, $args) {
            return $item->name === $feature;
        }, false);

        if ($featureFound === false) {
            $featureFound = new FeatureFlag($feature, $this->getDefaultStatus($feature, $this->defaultStatus, ... $args));
        }

        return (new FeatureFlag(
            $featureFound->name,
            $this->getDefaultStatus($featureFound->name, $featureFound->enabled(), ... $args),
            $featureFound->description,
            $featureFound->project,
            $featureFound->stale,
            $featureFound->type,
            $featureFound->strategies->toArray(),
            $featureFound->variants->toArray()
        ))->withTestArgs(... $featureFound->testArgs);
    }

    protected function getDefaultStatus($feature, $status, ... $args) {
        if (is_callable($this->defaultStatusUsing)) {
            $statusUsing = $this->defaultStatusUsing;
            return $statusUsing($feature, $status, ... $args);
        }

        return $status ?? false;
    }

    protected function _enabled($name, array $args): bool
    {
        $flag = $this->getFakeFeature($name, $args);
        $status = $flag->enabled();
        if (!is_callable($flag->testArgsUsing) && $flag->testArgs !== $args) {
            $status = $this->getDefaultStatus($name, $this->defaultStatus, ... $args);
        }

        return $status === true;
    }

    protected function getFakeFeatures(array $features): FeatureFlagCollection
    {
        if (!isset($features[0])) {
            return FeatureFlagCollection::empty();
        }

        if ($features[0] instanceof FeatureFlag) {
            $features = FeatureFlagCollection::wrap($features);
        } elseif ($features[0] instanceof FeatureFlagCollection) {
            $features = $features[0];
        } elseif (is_array($features[0]) && is_string($features[0][0])) {
            $collection = new FeatureFlagCollection();
            foreach ($features[0] as $feature) {
                $collection->add((new FeatureFlag($feature, true))->withTestArgsAny());
            }
            $features = $collection;
        } else {
            throw new \RuntimeException("Unknown feature values. Features must be a list of FeatureFlags, a FeatureFlagCollection, or an array of feature flag names");
        }
        return $features;
    }
}