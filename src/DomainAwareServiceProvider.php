<?php namespace Orchestra\Extension;

use Illuminate\Support\ServiceProvider;

class DomainAwareServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->afterResolving('Orchestra\Extension\RouteGenerator', function ($generator, $app) {
            $generator->setBaseUrl($app['config']->get('app.url'));
        });
    }
}
