<?php

namespace MikeFrancis\LaravelUnleash;

use function GuzzleHttp\json_decode;

use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class Unleash
{
    private $client;

    private $cache;

    private $config;

    private $request;

    private $features = [];

    public function __construct(ClientInterface $client, Cache $cache, Config $config, Request $request)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->config = $config;
        $this->request = $request;

        if (!$this->config->get('unleash.isEnabled')) {
            return;
        }

        if ($this->config->get('unleash.cache.isEnabled')) {
            $this->features = $this->cache->remember(
                'unleash',
                $this->config->get('unleash.cache.ttl'),
                function () {
                    return $this->fetchFeatures();
                }
            );
        } else {
            $this->features = $this->fetchFeatures();
        }
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function getFeature(string $name)
    {
        $features = $this->getFeatures();

        return Arr::first(
            $features,
            function (array $unleashFeature) use ($name) {
                return $name === $unleashFeature['name'];
            }
        );
    }

    public function isFeatureEnabled(string $name): bool
    {
        $feature = $this->getFeature($name);
        $isEnabled = Arr::get($feature, 'enabled', false);

        if (!$isEnabled) {
            return false;
        }

        $strategies = Arr::get($feature, 'strategies', []);
        $allStrategies = $this->config->get('unleash.strategies', []);

        foreach ($strategies as $strategyData) {
            $className = $strategyData['name'];

            if (!array_key_exists($className, $allStrategies)) {
                return false;
            }

            $strategy = new $allStrategies[$className];

            if (!$strategy instanceof Strategy) {
                throw new \Exception("${$className} does not implement base Strategy.");
            }

            $params = Arr::get($strategyData, 'parameters', []);

            if (!$strategy->isEnabled($params, $this->request)) {
                return false;
            }
        }

        return $isEnabled;
    }

    public function isFeatureDisabled(string $name): bool
    {
        return !$this->isFeatureEnabled($name);
    }

    private function fetchFeatures(): array
    {
        try {
            $response = $this->client->get('/api/client/features');
            $data = json_decode((string) $response->getBody(), true);

            return Arr::get($data, 'features', []);
        } catch (\InvalidArgumentException $e) {
            return [];
        }
    }
}
