<?php

namespace Orchestra\Extension\Concerns;

use Orchestra\Extension\RouteGenerator;
use Illuminate\Contracts\Foundation\Application;

trait DomainAware
{
    /**
     * Register domain awareness from configuration.
     *
     * @return void
     */
    public function registerDomainAwareness()
    {
        $this->app->resolving(RouteGenerator::class, function (RouteGenerator $generator, Application $app) {
            $generator->setBaseUrl($app->make('config')->get('app.url'));
        });
    }
}
