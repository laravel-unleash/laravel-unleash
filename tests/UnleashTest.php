<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use ErrorException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategy;
use MikeFrancis\LaravelUnleash\Tests\Stubs\ImplementedStrategyThatIsDisabled;
use MikeFrancis\LaravelUnleash\Tests\Stubs\NonImplementedStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpFoundation\Exception\JsonException;

class UnleashTest extends TestCase
{
    use MockClient;

    public function testFeatureDetectionCanBeDisabled()
    {
        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertFalse($unleash->enabled('someFeature'));
    }

    public function testAll()
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => 'someFeature',
                                'enabled' => true,
                            ],
                            [
                                'name' => 'anotherFeature',
                                'enabled' => false,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $features = $unleash->all();
        $this->assertInstanceOf(FeatureFlagCollection::class, $features);
        $this->assertCount(2, $features);
        $this->assertEquals(new FeatureFlag('someFeature', true), $features[0]);
        $this->assertEquals(new FeatureFlag('anotherFeature', false), $features[1]);
    }

    public function testGet()
    {
        $this->mockHandler->append(
            new Response(
                200,
                [],
                json_encode(
                    [
                        'features' => [
                            [
                                'name' => 'someFeature',
                                'enabled' => true,
                            ],
                        ],
                    ]
                )
            )
        );

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $feature = $unleash->get('someFeature');
        $this->assertInstanceOf(FeatureFlag::class, $feature);
        $this->assertEquals(new FeatureFlag('someFeature', true), $feature);
        $this->assertArrayHasKey('enabled', $feature);
        $this->assertTrue($feature['enabled']);
    }

    public function testEnabled()
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertTrue($unleash->enabled($featureName));
    }

    public function testEnabledWithValidStrategy()
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'testStrategy' => ImplementedStrategy::class,
        ]);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertTrue($unleash->enabled($featureName));
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'testStrategy' => ImplementedStrategy::class,
            'testStrategyThatIsDisabled' => ImplementedStrategyThatIsDisabled::class,
        ]);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertTrue($unleash->enabled($featureName));
    }

    public function testEnabledWithInvalidStrategy()
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'invalidTestStrategy' => ImplementedStrategy::class,
        ]);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $this->assertFalse($unleash->enabled($featureName));
    }

    public function testEnabledWithStrategyThatDoesNotImplementBaseStrategy()
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'testStrategy' => NonImplementedStrategy::class,
        ]);

        $request = $this->createMock(Request::class);

        $this->expectException(ErrorException::class);
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);
        $unleash->enabled($featureName);
    }

    public function testDisabled()
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

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertTrue($unleash->disabled($featureName));
    }
}
