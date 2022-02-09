<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Tests\MockClient;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use Orchestra\Testbench\TestCase;

class DynamicStrategyTest extends TestCase
{
    use MockClient;

    public function testWithoutArgs()
    {
        $featureName = 'someFeature';

        $this->setMockHandler($featureName);

        $cache = $this->createMock(Cache::class);

        $request = $this->createMock(Request::class);

        $strategy = $this->createMock(ImplementedStrategy::class);
        $strategy->expects($this->exactly(2))->method('isEnabled')
            ->with([], $request)
            ->willReturn(true);

        $this->setMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertTrue($unleash->enabled($featureName));
        $this->assertFalse($unleash->disabled($featureName));
    }

    public function testWithArg()
    {
        $featureName = 'someFeature';

        $this->setMockHandler($featureName);

        $cache = $this->createMock(Cache::class);

        $request = $this->createMock(Request::class);

        $strategy = $this->createMock(ImplementedStrategy::class);
        $strategy->expects($this->exactly(2))->method('isEnabled')
            ->with([], $request, true)
            ->willReturn(true);

        $this->setMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertTrue($unleash->enabled($featureName, true));
        $this->assertFalse($unleash->disabled($featureName, true));
    }

    public function testWithArgs()
    {
        $featureName = 'someFeature';

        $this->setMockHandler($featureName);

        $cache = $this->createMock(Cache::class);

        $request = $this->createMock(Request::class);

        $strategy = $this->createMock(ImplementedStrategy::class);
        $strategy->expects($this->exactly(2))->method('isEnabled')
            ->with([], $request, 'foo', 'bar', 'baz')
            ->willReturn(true);

        $this->setMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertTrue($unleash->enabled($featureName, 'foo', 'bar', 'baz'));
        $this->assertFalse($unleash->disabled($featureName, 'foo', 'bar', 'baz'));
    }

    /**
     * @param  \PHPUnit\Framework\MockObject\MockObject $strategy
     * @return Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function setMockConfig(\PHPUnit\Framework\MockObject\MockObject $strategy)
    {
        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'testStrategy' => function () use ($strategy) {
                return $strategy;
            },
        ]);
    }

    /**
     * @param string $featureName
     */
    protected function setMockHandler(string $featureName): void
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => true,
                                'strategies' => [
                                    [
                                        'name' => 'testStrategy',
                                    ],
                                ],
                            ],
                        ],
                    ]
                )
            )
        );
    }
}
