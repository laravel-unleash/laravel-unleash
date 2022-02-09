<?php

namespace MikeFrancis\LaravelUnleash\Values;

use Illuminate\Support\Collection;
use Illuminate\Support\ItemNotFoundException;
use lastguest\Murmur;
use MikeFrancis\LaravelUnleash\Exception\VariantNotFoundException;
use MikeFrancis\LaravelUnleash\ReadonlyArray;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\DynamicStrategy;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy as StdStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Unleash\Context;

class FeatureFlag implements \ArrayAccess
{
    use ReadonlyArray;

    protected $unleash;
    protected $name;
    protected $enabled;
    protected $description;
    protected $project;
    protected $stale;
    protected $type;
    protected $strategies;
    protected $variants;

    private $testArgsUsing = null;
    private $testArgs = [];

    public function __construct(string $name, bool $enabled, string $description = '', string $project = 'default', bool $stale = false, string $type = 'release', array $strategies = [], array $variants = [])
    {
        $this->unleash = resolve(Unleash::class);

        $this->strategies = Collection::wrap($strategies)->map(function($item) {
            return new Strategy($item['name'], $item['parameters'] ?? null);
        });
        $this->enabled = $enabled;
        $this->name = $name;
        $this->description = $description;
        $this->project = $project;
        $this->stale = $stale;
        $this->type = $type;
        $this->variants = Collection::wrap($variants)->map(function ($item) {
            return new Variant(
                $item['name'],
                $item['weight'],
                $item['weightType'],
                $item['stickiness'],
                $item['payload'],
                $item['overrides'] ?? []
            );
        });
    }

    /**
     * @internal
     */
    public function withTestArgs(... $args)
    {
        $this->testArgs = $args;
        return $this;
    }

    /**
     * @internal
     */
    public function withTestArgsUsing(callable $callable)
    {
        $this->testArgsUsing = $callable;
        return $this;
    }

    /**
     * @internal
     */
    public function withTestArgsAny()
    {
        $this->withTestArgsUsing(function() { return true; });
        return $this;
    }

    public function __isset($name)
    {
        return isset($this->{$name});
    }

    public function __get($name)
    {
        return $this->{$name};
    }

    public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

    public function offsetGet($offset)
    {
        return $this->{$offset};
    }

    public function enabled(... $args): bool
    {
        if (!$this->enabled) {
            return false;
        }

        return $this->applyStrategies($args);
    }

    public function disabled(): bool
    {
        return !$this->enabled;
    }

    public function variant($default, ?Context $context): Variant
    {
        if (!$this->enabled() || $this->variants->count() === 0) {
            return new Variant('default', 0, 'fixed', 'default', ['type' => 'default', 'value' => $default], []);
        }

        try {
            if ($context === null) {
                $context = new Context($this->unleash->request);
            }

            return $this->selectVariant($context);
        } catch (VariantNotFoundException $e) {
            return new Variant('default', 0, 'fixed', 'default', ['type' => 'default', 'value' => $default], []);
        }
    }

    protected function applyStrategies($args): bool
    {
        if ($this->strategies->isEmpty()) {
            return true;
        }

        $allStrategies = Collection::wrap($this->unleash->config->get('unleash.strategies'));

        foreach ($this->strategies as $strategyData) {
            $className = $strategyData->name;

            if (!$allStrategies->has($className)) {
                continue;
            }

            if (is_callable($allStrategies[$className])) {
                $strategy = $allStrategies[$className]();
            } else {
                $strategy = new $allStrategies[$className];
            }

            if (!$strategy instanceof StdStrategy && !$strategy instanceof DynamicStrategy) {
                throw new \Exception("${$className} does not implement base Strategy/DynamicStrategy.");
            }

            $params = $strategyData->parameters ?? [];

            if ($strategy->isEnabled($params, $this->unleash->request, ...$args)) {
                return true;
            }
        }

        return false;
    }

    protected function selectVariant(Context $context)
    {
        $totalWeight = $this->variants->reduce(function(int $carry, Variant $item) {
            return $carry += $item->weight;
        }, 0);

        if ($totalWeight <= 0) {
            throw new VariantNotFoundException();
        }

        try {
            return $this->findOverride($context);
        } catch (ItemNotFoundException $e) {
            $stickiness = $this->calculateStickiness($context, $totalWeight);

            return $this->findVariant($stickiness);
        }
    }

    protected function findOverride(Context $context)
    {
        return $this->variants->firstOrFail(function (Variant $item) use ($context) {
            if (!isset($item->overrides) || $item->overrides->isEmpty()) {
                return false;
            }

            return $item->overrides->first(function($item) use ($context) {
                return in_array(
                    $context->getContextValue($item->contextName),
                    $item->values
                );
            }, false);
        });
    }

    protected function calculateStickiness(Context $context, int $totalWeight): int
    {
        $stickUsing = $this->variants->first()->stickiness;
        if ($stickUsing !== 'default') {
            $seed = $context->getContextValue($stickUsing) ?? $this->randomString();
        } else {
            $seed = $context->getUserId() ?? $context->getSessionId() ?? $context->getIpAddress() ?? $this->randomString();
        }

        return Murmur::hash3_int("{$this->name}:{$seed}") % $totalWeight + 1;
    }

    protected function findVariant(int $stickiness)
    {
        $threshold = 0;
        $variant = $this->variants->first(function ($item) use (&$threshold, $stickiness) {
            if ($item->overrides->count() > 0 || $item->weight <= 0) {
                return false;
            }
            $threshold += $item->weight;
            if ($threshold >= $stickiness) {
                return true;
            }
            return false;
        }, false);

        if (!$variant) {
            throw new VariantNotFoundException();
        }

        return $variant;
    }

    protected function randomString(): string
    {
        return bin2hex(random_bytes(random_int(0, 100000)));
    }
}