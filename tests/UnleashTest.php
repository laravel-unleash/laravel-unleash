<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategy;
use MikeFrancis\LaravelUnleash\Tests\Stubs\NonImplementedStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use PHPUnit\Framework\TestCase;

class UnleashTest extends TestCase
{
    protected $mockHandler;

    protected $client;

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

    public function testFeaturesCanBeCached()
    {
        $featureName = 'someFeature';

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('remember')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName));
    }

    public function testFeatureDetectionCanBeDisabled()
    {
        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertFalse($unleash->isFeatureEnabled('someFeature'));
    }

    public function testCanHandleErrorsFromUnleash()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(new Response(200, [], 'lol'));

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureDisabled($featureName));
    }

    public function testIsFeatureEnabled()
    {
        $featureName = 'someFeature';

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
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName));
    }

    public function testGetAvailableFeaturesCollection()
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => 'featureName 1',
                                'enabled' => true,
                            ],
                            [
                                'name' => 'featureName 2',
                                'enabled' => false,
                            ],
                            [
                                'name' => 'featureName 3',
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $availableFeaturesCollection = $unleash->getAvailableFeaturesCollection();
        $this->assertCount(3, $availableFeaturesCollection);
        $this->assertEquals(['featureName 1' => true], $availableFeaturesCollection->first());
        $this->assertEquals(['featureName 2' => false], $availableFeaturesCollection[1]);
        $this->assertEquals(['featureName 3' => true], $availableFeaturesCollection->last());
    }

    public function testIsFeatureEnabledWithValidStrategy()
    {
        $featureName = 'someFeature';

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

        $cache = $this->createMock(Cache::class);

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
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName));
    }

    public function testIsFeatureDisabledWithInvalidStrategy()
    {
        $featureName = 'someFeature';

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

        $cache = $this->createMock(Cache::class);

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
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'invalidTestStrategy' => ImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertFalse($unleash->isFeatureEnabled($featureName));
    }

    public function testIsFeatureDisabledWithStrategyThatDoesNotImplementBaseStrategy()
    {
        $featureName = 'someFeature';

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

        $cache = $this->createMock(Cache::class);

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
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => NonImplementedStrategy::class,
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        try {
            $unleash->isFeatureEnabled($featureName);
        } catch (\Exception $e) {
            $this->assertNotEmpty($e);
        }
    }

    public function testIsFeatureDisabled()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => $featureName,
                                'enabled' => false,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureDisabled($featureName));
    }
}
