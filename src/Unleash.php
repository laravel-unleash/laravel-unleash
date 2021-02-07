<?php

namespace MikeFrancis\LaravelUnleash;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\ClientInterface;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\DynamicStrategy;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;

class Unleash
{
    const DEFAULT_CACHE_TTL = 15;

    protected $client;
    protected $cache;
    protected $config;
    protected $request;
    protected $features;

    public function __construct(ClientInterface $client, Cache $cache, Config $config, Request $request)
    {
        $this->client = $client;
        $this->cache = $cache;
        $this->config = $config;
        $this->request = $request;
    }

    public function getFeatures(): array
    {
        try {
            $features = $this->getCachedFeatures();

            // Always store the failover cache, in case it is turned on during failure scenarios.
            $this->cache->forever('unleash.features.failover', $features);

            return $features;
        } catch (RequestException | \RuntimeException $e) {
            if ($this->config->get('unleash.cache.failover') === true) {
                return $this->cache->get('unleash.features.failover', []);
            }
        }

        return [];
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

    public function isFeatureEnabled(string $name, ...$args): bool
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

            if (is_callable($allStrategies[$className])) {
                $strategy = $allStrategies[$className]();
            } else {
                $strategy = new $allStrategies[$className];
            }

            if (!$strategy instanceof Strategy && !$strategy instanceof DynamicStrategy) {
                throw new \Exception("${$className} does not implement base Strategy/DynamicStrategy.");
            }

            $params = Arr::get($strategyData, 'parameters', []);

            if (!$strategy->isEnabled($params, $this->request, ...$args)) {
                return false;
            }
        }

        return $isEnabled;
    }

    public function isFeatureDisabled(string $name, ...$args): bool
    {
        return !$this->isFeatureEnabled($name, ...$args);
    }

    protected function getCachedFeatures(): array
    {
        if (!$this->config->get('unleash.isEnabled')) {
            return [];
        }

        if ($this->config->get('unleash.cache.isEnabled')) {
            return $this->cache->remember(
                'unleash',
                $this->config->get('unleash.cache.ttl', self::DEFAULT_CACHE_TTL),
                function () {
                    return $this->fetchFeatures();
                }
            );
        }

        return $this->features ?? $this->features = $this->fetchFeatures();
    }

    protected function fetchFeatures(): array
    {
        $response = $this->client->get($this->getFeaturesApiUrl(), $this->getRequestOptions());
        if ($response->getStatusCode() != 200) {
            throw new \RuntimeException("Unleash Request Failed");
        }

        $data = json_decode((string) $response->getBody(), true);
        if ($data === null) {
            throw new \RuntimeException("JSON: Syntax Error", 4);
        }

        return $this->formatResponse($data);
    }
    
    protected function getFeaturesApiUrl(): string
    {
        return '/api/client/features';
    }

    protected function getRequestOptions(): array
    {
        return [];
    }

    protected function formatResponse($data): array
    {
        return Arr::get($data, 'features', []);
    }
}
