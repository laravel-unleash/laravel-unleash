<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Values\FeatureFlag;
use MikeFrancis\LaravelUnleash\Values\FeatureFlagCollection;
use Orchestra\Testbench\TestCase;

class UnleashCachingTest extends TestCase
{
    use MockClient;

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
                FeatureFlagCollection::make()->add(new FeatureFlag($featureName, true))
            );

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', true);
        Config::set('unleash.cache.ttl', null);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName));
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName));
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
            new Response(200, [], '{"broken" json]')
        );

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', true);
        Config::set('unleash.cache.ttl', 0.1);
        Config::set('unleash.cache.failover', true);

        $request = $this->createMock(Request::class);


        $unleash = new Unleash($this->client, resolve(Cache::class), Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");

        usleep(2000);

        $unleash = new Unleash($this->client, resolve(Cache::class), Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request");
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
            new Response(200, [], '{"broken" json]')
        );


        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', true);
        Config::set('unleash.cache.ttl', 0.1);
        Config::set('unleash.cache.failover', false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, resolve(Cache::class), Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");

        usleep(2000);

        $unleash = new Unleash($this->client, resolve(Cache::class), Config::getFacadeRoot(), $request);
        $this->assertFalse($unleash->enabled($featureName), "Cached Request");
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

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->at(0))
            ->method('forever')
            ->with('unleash.features.failover', new FeatureFlagCollection([
                new FeatureFlag($featureName, true)
            ]));
        $cache->expects($this->at(1))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.cache.failover', true);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request");
    }

    public function testFeaturesCacheIgnoreInvalidCacheData()
    {
        $featureName = 'someFeature';

        $response = new Response(
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
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->at(0))
            ->method('remember')
            ->willReturn([]);
        $cache->expects($this->at(1))
            ->method('forget')
            ->with('unleash');
        $cache->expects($this->at(2))
            ->method('remember')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );
        $cache->expects($this->at(3))
            ->method('remember')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', true);
        Config::set('unleash.cache.ttl', 3600);
        Config::set('unleash.cache.failover', false);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName));

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName));

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName));
        $this->assertEquals(0, $this->mockHandler->count());
    }

    public function testCanHandleErrorsFromUnleashWithFailover()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(new Response(200, [], 'lol'));

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(FeatureFlagCollection::empty());

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertTrue($unleash->disabled($featureName));
    }

    public function testCanHandleErrorsFromUnleashWithoutFailover()
    {
        $featureName = 'someFeature';

        $this->mockHandler->append(new Response(200, [], 'lol'));
        $this->mockHandler->append(new Response(200, [], 'lol'));

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.cache.failover', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $this->assertFalse($unleash->enabled($featureName));
        $this->assertTrue($unleash->disabled($featureName));
    }

    public function testCacheFailoverOnce()
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
        $this->mockHandler->append(
            new Response(500)
        );
        $this->mockHandler->append(
            new Response(500)
        );
        $this->mockHandler->append(
            new Response(500)
        );
        $this->mockHandler->append(
            new Response(500)
        );

        $cache = $this->createMock(Cache::class);
        $cache->expects($this->exactly(1))
            ->method('forever')
            ->with('unleash.features.failover', new FeatureFlagCollection([
                new FeatureFlag($featureName, true)
            ]));
        $cache->expects($this->exactly(5))
            ->method('get')
            ->with('unleash.features.failover')
            ->willReturn(
                new FeatureFlagCollection([
                    new FeatureFlag($featureName, true)
                ])
            );

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.cache.failover', true);

        $request = $this->createMock(Request::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Uncached Request");
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request #1");
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request #2");
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request #3");
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request #4");
        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertTrue($unleash->enabled($featureName), "Cached Request #5");
    }
}