<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use MikeFrancis\LaravelUnleash\Strategies\Contracts\Strategy;
use MikeFrancis\LaravelUnleash\Unleash;
use PHPUnit\Framework\TestCase;

class TestImplementedStrategy implements Strategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    return true;
  }
}

class TestNonImplementedStrategy
{
  public function isEnabled(array $params, Request $request): bool
  {
    return true;
  }
}

class UnleashTest extends TestCase
{
  protected $mockHandler;

  protected $client;

  protected function setUp(): void
  {
    parent::setUp();

    $this->mockHandler = new MockHandler();

    $this->client = new Client([
      'handler' => $this->mockHandler,
    ]);
  }

  public function testFeaturesCanBeCached()
  {
    $featureName = 'someFeature';

    $cache = $this->createMock(Cache::class);
    $cache->expects($this->once())
      ->method('remember')
      ->willReturn([
        [
          'name' => $featureName,
          'enabled' => true,
        ],
      ]);

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

    $this->mockHandler->append(new Response(200, [], json_encode([
      'features' => [
        [
          'name' => $featureName,
          'enabled' => true,
        ],
      ],
    ])));

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

  public function testIsFeatureEnabledWithValidStrategy()
  {
    $featureName = 'someFeature';

    $this->mockHandler->append(new Response(200, [], json_encode([
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
    ])));

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
      ->willReturn([
        'testStrategy' => TestImplementedStrategy::class,
      ]);

    $request = $this->createMock(Request::class);

    $unleash = new Unleash($this->client, $cache, $config, $request);

    $this->assertTrue($unleash->isFeatureEnabled($featureName));
  }

  public function testIsFeatureDisabledWithInvalidStrategy()
  {
    $featureName = 'someFeature';

    $this->mockHandler->append(new Response(200, [], json_encode([
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
    ])));

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
      ->willReturn([
        'invalidTestStrategy' => TestImplementedStrategy::class,
      ]);

    $request = $this->createMock(Request::class);

    $unleash = new Unleash($this->client, $cache, $config, $request);

    $this->assertFalse($unleash->isFeatureEnabled($featureName));
  }

  public function testIsFeatureDisabledWithStrategyThatDoesNotImplementBaseStrategy()
  {
    $featureName = 'someFeature';

    $this->mockHandler->append(new Response(200, [], json_encode([
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
    ])));

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
      ->willReturn([
        'testStrategy' => TestNonImplementedStrategy::class,
      ]);

    $request = $this->createMock(Request::class);

    $unleash = new Unleash($this->client, $cache, $config, $request);

    try {
      $unleash->isFeatureEnabled($featureName);
    } catch (\Exception $e) {
      $this->assertStringContainsString('does not implement base Strategy', $e->getMessage());
    }
  }

  public function testIsFeatureDisabled()
  {
    $featureName = 'someFeature';

    $this->mockHandler->append(new Response(200, [], json_encode([
      'features' => [
        [
          'name' => $featureName,
          'enabled' => false,
        ],
      ],
    ])));

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
