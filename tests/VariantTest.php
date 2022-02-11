<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use MikeFrancis\LaravelUnleash\Exception\UnknownVariantTypeException;
use MikeFrancis\LaravelUnleash\Strategies\UserWithIdStrategy;
use MikeFrancis\LaravelUnleash\Unleash;
use MikeFrancis\LaravelUnleash\Unleash\Context;
use MikeFrancis\LaravelUnleash\Values\Variant;
use MikeFrancis\LaravelUnleash\Values\Variant\PayloadCSV;
use MikeFrancis\LaravelUnleash\Values\Variant\PayloadDefault;
use MikeFrancis\LaravelUnleash\Values\Variant\PayloadJSON;
use MikeFrancis\LaravelUnleash\Values\Variant\PayloadString;
use Orchestra\Testbench\TestCase;

class VariantTest extends TestCase
{
    use MockClient;

    public function testFeatureEnabledWithVariantString()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing 2', $variant->payload->value);
    }

    public function testFeatureEnabledWithVariantJSON()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'json',
                                        'value' => '{"foo": "bar", "baz": "bat"}',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'json',
                                        'value' => '{"bar": "foo", "bat": "baz"}',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadJSON::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->foo));
        $this->assertTrue(isset($variant->payload->baz));
        $this->assertEquals('bar', $variant->payload->foo);
        $this->assertEquals('bat', $variant->payload->baz);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadJSON::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->foo));
        $this->assertTrue(isset($variant->payload->baz));
        $this->assertEquals('bar', $variant->payload->foo);
        $this->assertEquals('bat', $variant->payload->baz);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadJSON::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->bar));
        $this->assertTrue(isset($variant->payload->bat));
        $this->assertEquals('foo', $variant->payload->bar);
        $this->assertEquals('baz', $variant->payload->bat);
    }

    public function testFeatureEnabledWithVariantCSV()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'csv',
                                        'value' => '1,2,3',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'csv',
                                        'value' => '4,5,6',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadCSV::class, $variant->payload);
        $this->assertEquals([1,2,3], $variant->payload->values);
        $this->assertEquals(1, $variant->payload[0]);
        $this->assertEquals(2, $variant->payload[1]);
        $this->assertEquals(3, $variant->payload[2]);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadCSV::class, $variant->payload);
        $this->assertEquals([1,2,3], $variant->payload->values);
        $this->assertEquals(1, $variant->payload[0]);
        $this->assertEquals(2, $variant->payload[1]);
        $this->assertEquals(3, $variant->payload[2]);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadCSV::class, $variant->payload);
        $this->assertEquals([4,5,6], $variant->payload->values);
        $this->assertEquals(4, $variant->payload[0]);
        $this->assertEquals(5, $variant->payload[1]);
        $this->assertEquals(6, $variant->payload[2]);
    }

    public function testFeatureEnabledWithVariantCSVReadOnly()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'csv',
                                        'value' => '1,2,3',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'csv',
                                        'value' => '4,5,6',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertTrue(isset($variant->payload));
        $this->assertInstanceOf(PayloadCSV::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->values));
        $this->assertEquals([1,2,3], $variant->payload->values);
        $this->assertTrue(isset($variant->payload[0]));
        $this->assertEquals(1, $variant->payload[0]);
        $this->assertEquals(2, $variant->payload[1]);
        $this->assertEquals(3, $variant->payload[2]);

        try {
            $variant->payload[0] = 2;
        } catch (\Exception $e) {
            $this->assertInstanceOf(\BadMethodCallException::class, $e);
        }

        try {
            $variant->payload[] = 2;
        } catch (\Exception $e) {
            $this->assertInstanceOf(\BadMethodCallException::class, $e);
        }

        try {
            unset($variant->payload[0]);
        } catch (\Exception $e) {
            $this->assertInstanceOf(\BadMethodCallException::class, $e);
        }
    }

    public function testFeatureEnabledWithVariantUnknown()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'unknown',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->once())->method('getAuthIdentifier')->willReturn(1);
        $request = $this->createMock(Request::class);
        $request->expects($this->once())->method('user')->willReturn($userMock);

        $this->expectException(UnknownVariantTypeException::class);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $unleash->variant($featureName, null, new Context($request));
    }

    public function testFeatureEnabledWithVariantNoContext()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing 2', $variant->payload->value);
    }

    public function testFeatureEnabledWithVariantWithStickiness()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'userId',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'userId',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing', $variant->payload->value);
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertEquals('testing 2', $variant->payload->value);
    }

    public function testFeatureEnabledWithVariantWithNullStickiness()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'sessionId',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'sessionId',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertTrue(in_array($variant->payload->value, ['testing', 'testing 2']));
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertTrue(in_array($variant->payload->value, ['testing', 'testing 2']));
        $variant = $unleash->variant($featureName);
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertTrue(isset($variant->payload->value));
        $this->assertTrue(in_array($variant->payload->value, ['testing', 'testing 2']));
    }

    public function testFeatureEnabledWithVariantStringWithOverrides()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => [
                                        [
                                            'contextName' => 'userId',
                                            'values' => ['3'],
                                        ],
                                    ]
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => [
                                        [
                                            'contextName' => 'userId',
                                            'values' => ['1', '2'],
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(4))->method('getAuthIdentifier')->willReturn(1, 2, 3, 4);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(4))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertEquals('testing 2', $variant->payload->value);
        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertEquals('testing 2', $variant->payload->value);
        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadString::class, $variant->payload);
        $this->assertEquals('testing', $variant->payload->value);
        $this->assertTrue(isset($variant->overrides));
        $this->assertTrue(isset($variant->overrides[0]->contextName));

        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(Variant::class, $variant);
        $this->assertInstanceOf(PayloadDefault::class, $variant->payload);
        $this->assertNull($variant->payload->value);
    }

    public function testFeatureEnabledWithStrategyWithVariant()
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
                            'strategies' => [
                                [
                                    "name" => "userWithId",
                                    "constraints" => [],
                                    "parameters" => [
                                        "userIds" => "1,3",
                                    ],
                                ],
                            ],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'userWithId' => UserWithIdStrategy::class,
        ]);

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(9))->method('getAuthIdentifier')->willReturn(1, 1, 1, 2, 2, 2, 3, 3, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(9))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $feature = $unleash->get($featureName);
        $this->assertTrue($feature->enabled());
        $this->assertEquals("testing", $feature->variant(null, new Context($request))->payload->value);
        $feature = $unleash->get($featureName);
        $this->assertFalse($feature->enabled());
        $this->assertEquals(null, $feature->variant(null, new Context($request))->payload->value);
        $feature = $unleash->get($featureName);
        $this->assertTrue($feature->enabled());
        $this->assertEquals("testing 2", $feature->variant(null, new Context($request))->payload->value);
    }

    public function testFeatureEnabledWithStrategyWithVariantWithOverrides()
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
                            'strategies' => [
                                [
                                    "name" => "userWithId",
                                    "constraints" => [],
                                    "parameters" => [
                                        "userIds" => "1,3",
                                    ],
                                ],
                            ],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => [
                                        [
                                            'contextName' => 'userId',
                                            'values' => ['3']
                                        ]
                                    ]
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => [
                                        [
                                            'contextName' => 'userId',
                                            'values' => ['1', '2']
                                        ]
                                    ]
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);
        $this->mockHandler->append($response);
        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');
        Config::set('unleash.strategies', [
            'userWithId' => UserWithIdStrategy::class,
        ]);

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(9))->method('getAuthIdentifier')->willReturn(1, 1, 1, 2, 2, 2, 3, 3, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(9))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->instance(Unleash::class, $unleash);

        $feature = $unleash->get($featureName);
        $this->assertTrue($feature->enabled());
        $this->assertEquals("testing 2", $feature->variant(null, new Context($request))->payload->value);
        $feature = $unleash->get($featureName);
        $this->assertFalse($feature->enabled());
        $this->assertEquals(null, $feature->variant(null, new Context($request))->payload->value);
        $feature = $unleash->get($featureName);
        $this->assertTrue($feature->enabled());
        $this->assertEquals("testing", $feature->variant(null, new Context($request))->payload->value);
    }

    public function testFeatureEnabledWithVariantNotFound()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 0,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $variant = $unleash->variant($featureName, null, new Context($request));
        $this->assertInstanceOf(PayloadDefault::class, $variant->payload);
        $this->assertNull($variant->payload->value);
        $variant = $unleash->variant($featureName, true, new Context($request));
        $this->assertInstanceOf(PayloadDefault::class, $variant->payload);
        $this->assertTrue($variant->payload->value);
        $variant = $unleash->variant($featureName, ['bing' => 'qux'], new Context($request));
        $this->assertInstanceOf(PayloadDefault::class, $variant->payload);
        $this->assertEquals(['bing' => 'qux'], $variant->payload->value);
    }

    public function testFeatureEnabledWithVariantUnweighted()
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
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 100,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => [
                                        [
                                            'contextName' => 'userId',
                                            'values' => ['3']
                                        ]
                                    ]
                                ],
                                [
                                    'name' => 'testVariant',
                                    'weight' => 0,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing 2',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertNull($unleash->variant($featureName, null, new Context($request))->payload->value);
        $this->assertTrue($unleash->variant($featureName, true, new Context($request))->payload->value);
        $this->assertEquals('testing', $unleash->variant($featureName, ['bing' => 'qux'], new Context($request))->payload->value);
    }

    public function testFeatureDisabledWithVariant()
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
                            'enabled' => false,
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertNull($unleash->variant($featureName, null, new Context($request))->payload->value);
        $this->assertTrue($unleash->variant($featureName, true, new Context($request))->payload->value);
        $this->assertEquals(['bing' => 'qux'], $unleash->variant($featureName, ['bing' => 'qux'], new Context($request))->payload->value);
    }

    public function testFeatureDisabledWithVariantWithOverride()
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
                            'enabled' => false,
                            'strategies' => [],
                            'variants' => [
                                [
                                    'name' => 'testVariant',
                                    'weight' => 500,
                                    'weightType' => 'fixed',
                                    'stickiness' => 'default',
                                    'payload' => [
                                        'type' => 'string',
                                        'value' => 'testing',
                                    ],
                                    'overrides' => []
                                ],
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->mockHandler->append($response);

        $cache = $this->createMock(Cache::class);

        Config::set('unleash.isEnabled', true);
        Config::set('unleash.cache.isEnabled', false);
        Config::set('unleash.featuresEndpoint', '/api/client/features');

        $userMock = $this->createMock(Authenticatable::class);
        $userMock->expects($this->exactly(3))->method('getAuthIdentifier')->willReturn(1, 2, 3);
        $request = $this->createMock(Request::class);
        $request->expects($this->exactly(3))->method('user')->willReturn($userMock);

        $unleash = new Unleash($this->client, $cache, Config::getFacadeRoot(), $request);
        $this->assertNull($unleash->variant($featureName, null, new Context($request))->payload->value);
        $this->assertTrue($unleash->variant($featureName, true, new Context($request))->payload->value);
        $this->assertEquals(['bing' => 'qux'], $unleash->variant($featureName, ['bing' => 'qux'], new Context($request))->payload->value);
    }
}