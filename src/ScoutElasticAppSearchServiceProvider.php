<?php

namespace Konsulting\ScoutElasticAppSearch;

use Laravel\Scout\EngineManager;
use Elastic\AppSearch\Client\Client;
use Illuminate\Support\ServiceProvider;
use Elastic\AppSearch\Client\ClientBuilder;

class ScoutElasticAppSearchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('scout-elastic-app-search.php'),
            ], 'config');
        }

        resolve(EngineManager::class)->extend('elastic-app-search', function ($app) {
            return $app->make(ScoutElasticAppSearchEngine::class);
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'scout-elastic-app-search');

        // Register the main class to use with the facade
        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']->get('scout-elastic-app-search');

            return ClientBuilder::create($config['endpoint'], $config['api-key'])->build();
        });
    }
}
