<?php

namespace MikeFrancis\LaravelUnleash;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use MikeFrancis\LaravelUnleash\Unleash\Context;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use function GuzzleHttp\json_decode;

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

    public function all(): FeatureFlagCollection
    {
        try {
            $features = $this->getCachedFeatures();

            // Always store the failover cache, in case it is turned on during failure scenarios.
            $this->cache->forever('unleash.features.failover', $features);

            return $features;
        } catch (TransferException | JsonException $e) {
            if ($this->config->get('unleash.cache.failover') === true) {
                return $this->cache->get('unleash.features.failover', FeatureFlagCollection::empty());
            }
        }

        return FeatureFlagCollection::empty();
    }

    public function get(string $name): ?FeatureFlag
    {
        return $this->all()->first(
            function (FeatureFlag $unleashFeature) use ($name) {
                return $name === $unleashFeature->name;
            },
            null
        );
    }

    public function enabled(string $name, ...$args): bool
    {
        $feature = $this->get($name);
        if ($feature === null) {
            return false;
        }

        return $feature->enabled(...$args);
    }

    public function disabled(string $name, ...$args): bool
    {
        return !$this->enabled($name, ...$args);
    }

    public function variant(string $name, $default = null, ?Context $context = null)
    {
        $feature = $this->get($name);
        if (!$feature) {
            return $default;
        }
        return $feature->variant($default, $context);
    }

    /**
     * @codeCoverageIgnore
     */
    public function isFeatureEnabled(string $feature, ...$args): bool
    {
        return static::enabled($feature, ...$args);
    }

    /**
     * @codeCoverageIgnore
     */
    public function isFeatureDisabled(string $feature, ...$args): bool
    {
        return static::disabled($feature, ...$args);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFeatures(): FeatureFlagCollection
    {
        return static::all();
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFeature(string $name)
    {
        return static::get($name);
    }

    /**
     * @codeCoverageIgnore
     */
    public function __isset($name)
    {
        return isset($this->{$name});
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    protected function getCachedFeatures(): FeatureFlagCollection
    {
        if (!$this->config->get('unleash.isEnabled')) {
            return FeatureFlagCollection::empty();
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

    protected function fetchFeatures(): FeatureFlagCollection
    {
        $response = $this->client->get($this->config->get('unleash.featuresEndpoint'));

        try {
            $data = json_decode((string)$response->getBody(), true, 512, \JSON_BIGINT_AS_STRING);
        } catch (InvalidArgumentException $e) {
            throw new JsonException('Could not decode unleash response body.', $e->getCode(), $e);
        }

        return $this->formatResponse($data);
    }

    protected function formatResponse($data): FeatureFlagCollection
    {
        return FeatureFlagCollection::wrap(Arr::get($data, 'features', []))->map(function ($item) {
            return new FeatureFlag(
                $item['name'],
                $item['enabled'],
                $item['description'] ?? '',
                $item['project'] ?? 'default',
                $item['stale'] ?? false,
                $item['type'] ?? 'release',
                $item['strategies'] ?? [],
                $item['variants'] ?? []
            );
        });
    }
}
