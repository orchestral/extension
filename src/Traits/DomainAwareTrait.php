<?php namespace Orchestra\Extension\Traits;

use Orchestra\Extension\RouteGenerator;
use Illuminate\Contracts\Foundation\Application;

trait DomainAwareTrait
{
    /**
     * Register domain awareness from configuration.
     *
     * @return void
     */
    public function registerDomainAwareness()
    {
        $this->app->afterResolving(function (RouteGenerator $generator, Application $app) {
            $generator->setBaseUrl($app->make('config')->get('app.url'));
        });
    }
}
