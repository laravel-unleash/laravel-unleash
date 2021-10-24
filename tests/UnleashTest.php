<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use Exception;
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
use Symfony\Component\HttpFoundation\Exception\JsonException;

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
        $cache->expects($this->exactly(2))
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
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(null);
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(null);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->isFeatureEnabled($featureName));
        $this->assertTrue($unleash->isFeatureEnabled($featureName));
    }

    public function testFeaturesCacheFailoverEnabled()
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
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(0))
            ->method('remember')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );
        $cache->expects($this->at(1))
            ->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );


        // Request 2
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(2))
            ->method('remember')
            ->willThrowException(new JsonException("Expected Failure: Testing"));

        $config->expects($this->at(7))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(true);
        $cache->expects($this->at(3))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName), "Uncached Request");
        usleep(200);
        $this->assertTrue($unleash->isFeatureEnabled($featureName), "Cached Request");
    }

    public function testFeaturesCacheFailoverDisabled()
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
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

        $config->expects($this->at(0))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(1))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(0))
            ->method('remember')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );
        $cache->expects($this->at(1))
            ->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );


        // Request 2
        $config->expects($this->at(4))
            ->method('get')
            ->with('unleash.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(5))
            ->method('get')
            ->with('unleash.cache.isEnabled')
            ->willReturn(true);
        $config->expects($this->at(6))
            ->method('get')
            ->with('unleash.cache.ttl')
            ->willReturn(0.1);

        $cache->expects($this->at(2))
            ->method('remember')
            ->willThrowException(new JsonException("Expected Failure: Testing"));

        $config->expects($this->at(7))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName), "Uncached Request");
        usleep(200);
        $this->assertFalse($unleash->isFeatureEnabled($featureName), "Cached Request");
    }

    public function testFeaturesCacheFailoverEnabledIndependently()
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
        $this->mockHandler->append(
            new Response(500)
        );

        $config = $this->createMock(Config::class);
        $cache = $this->createMock(Cache::class);

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

        $cache->expects($this->at(0))
            ->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ]
            );


        // Request 2
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
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(7))
            ->method('get')
            ->with('unleash.cache.failover')
            ->willReturn(true);

        $cache->expects($this->at(1))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->isFeatureEnabled($featureName), "Uncached Request");

        $unleash = new Unleash($this->client, $cache, $config, $request);
        $this->assertTrue($unleash->isFeatureEnabled($featureName), "Cached Request");
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
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

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
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureEnabled($featureName));
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
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(3))
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

    public function testIsFeatureEnabledWithMultipleStrategies()
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
                                    [
                                        'name' => 'testStrategyThatDoesNotMatch',
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
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(3))
            ->method('get')
            ->with('unleash.strategies')
            ->willReturn(
                [
                    'testStrategy' => ImplementedStrategy::class,
                ],
                [
                    'testStrategyThatDoesNotMatch' => ImplementedStrategyThatDoesNotMatch::class,
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
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(3))
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
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');
        $config->expects($this->at(3))
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
        } catch (Exception $e) {
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
        $config->expects($this->at(2))
            ->method('get')
            ->with('unleash.featuresEndpoint')
            ->willReturn('/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureDisabled($featureName));
    }
}
