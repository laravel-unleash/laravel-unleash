<?php

namespace MikeFrancis\LaravelUnleash\Tests\Strategies;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use PHPUnit\Framework\TestCase;

class DynamicStrategyTest extends TestCase
{
    protected $mockHandler;

    protected $client;

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

        $config = $this->getMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName));
        $this->assertFalse($unleash->isFeatureDisabled($featureName));
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

        $config = $this->getMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName, true));
        $this->assertFalse($unleash->isFeatureDisabled($featureName, true));
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

        $config = $this->getMockConfig($strategy);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName, 'foo', 'bar', 'baz'));
        $this->assertFalse($unleash->isFeatureDisabled($featureName, 'foo', 'bar', 'baz'));
    }

    /**
     * @param  \PHPUnit\Framework\MockObject\MockObject $strategy
     * @return Config|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMockConfig(\PHPUnit\Framework\MockObject\MockObject $strategy)
    {
        $config = $this->createMock(Config::class);

        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => function () use ($strategy) {
                        return $strategy;
                    },
                ]
            );
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);
        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => function () use ($strategy) {
                        return $strategy;
                    },
                ]
            );
        return $config;
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

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockHandler = new MockHandler();

        $this->client = new Client(
            [
                'handler' => $this->mockHandler,
            ]
        );
    }
}
