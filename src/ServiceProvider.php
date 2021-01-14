<?php

namespace Frknakk\Internetmarke;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    private $config_path = __DIR__ . '/../config/internetmarke.php';

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom($this->config_path, 'internetmarke');

        $this->app->bind('prodws', function ($app) {
            return new ProdWS\ProdWS($app['config']);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            $this->config_path => config_path('internetmarke.php'),
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['prodws'];
    }

}
