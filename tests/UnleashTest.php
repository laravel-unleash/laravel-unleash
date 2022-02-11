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
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategyThatIsDisabled;
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
        $cache->method('remember')
            ->willReturn(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ],
                ]
            );

        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled', 'unleash.cache.isEnabled' => true,
                    'unleash.strategies' => [
                        'testStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled',
                    'unleash.cache.isEnabled',
                    'unleash.cache.failover' => true,
                    'unleash.cache.ttl' => 0.1,
                    'unleash.strategies' => [
                        'testStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });


        $cache = $this->createMock(Cache::class);
        $cache->method('remember')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ]
                ],
                $this->throwException(new JsonException("Expected Failure: Testing")),
            );

        $cache->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

        $cache->method('get')
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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled',
                    'unleash.cache.isEnabled' => true,
                    'unleash.cache.failover' => false,
                    'unleash.cache.ttl' => 0.1,
                    'unleash.strategies' => [
                        'testStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });

        $cache = $this->createMock(Cache::class);
        $cache->method('remember')
            ->willReturnOnConsecutiveCalls(
                [
                    [
                        'name' => $featureName,
                        'enabled' => true,
                    ]
                ],
                $this->throwException(new JsonException("Expected Failure: Testing")),
            );

        $cache->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

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

        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled', 'unleash.cache.failover' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    'unleash.strategies' => [
                        'testStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });

        $cache = $this->createMock(Cache::class);

        $cache->method('forever')
            ->with('unleash.features.failover', [
                [
                    'name' => $featureName,
                    'enabled' => true,
                ],
            ]);

        $cache->method('get')
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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => false,
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    'unleash.strategies' => [
                            'testStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });

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
                                        'name' => 'testStrategyThatIsDisabled',
                                    ],
                                    [
                                        'name' => 'testStrategy',
                                    ]
                                ],
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    'unleash.strategies' => [
                        'testStrategy' => ImplementedStrategy::class,
                        'testStrategyThatIsDisabled' => ImplementedStrategyThatIsDisabled::class,
                    ],
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    'unleash.strategies' => [
                        'invalidTestStrategy' => ImplementedStrategy::class,
                    ],
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    'unleash.strategies' => [
                        'testStrategy' => NonImplementedStrategy::class,
                    ],
                    default => null
                };
            });

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
        $config->method('get')
            ->willReturnCallback(function($arg) {
                return match ($arg) {
                    'unleash.isEnabled' => true,
                    'unleash.cache.isEnabled' => false,
                    'unleash.featuresEndpoint' => '/api/client/features',
                    default => null
                };
            });

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, $config, $request);

        $this->assertTrue($unleash->isFeatureDisabled($featureName));
    }
}
