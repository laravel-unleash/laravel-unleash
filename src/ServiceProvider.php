<?php

namespace MikeFrancis\LaravelUnleash;

use GuzzleHttp\ClientInterface;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->getConfigPath(), 'unleash');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes(
            [
            $this->getConfigPath() => config_path('unleash.php'),
            ]
        );


        Blade::if(
            'featureEnabled',
            function (string $feature) {
                $client = app(Client::class);
                $unleash = app(Unleash::class, ['client' => $client]);

                return $unleash->isFeatureEnabled($feature);
            }
        );

        Blade::if(
            'featureDisabled',
            function (string $feature) {
                $client = app(Client::class);
                $unleash = app(Unleash::class, ['client' => $client]);

                return !$unleash->isFeatureEnabled($feature);
            }
        );

        // Interfaces
        $this->app->bind(ClientInterface::class, Client::class);
    }

    /**
     * Get the path to the config.
     *
     * @return string
     */
    private function getConfigPath(): string
    {
        return __DIR__ . '/../config/unleash.php';
    }
}
