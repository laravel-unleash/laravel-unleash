<?php

namespace MikeFrancis\LaravelUnleash\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use MikeFrancis\LaravelUnleash\ServiceProvider;

trait MockClient
{
    protected $mockHandler;

    protected $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupMockClient();
    }

    protected function setupMockClient(): void
    {
        $this->mockHandler = new MockHandler();

        $this->client = new Client(
            [
                'handler' => $this->mockHandler,
            ]
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }
}