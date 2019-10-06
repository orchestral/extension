<?php

namespace Orchestra\Extension\Concerns;

use Illuminate\Contracts\Container\Container;
use Orchestra\Extension\RouteGenerator;

trait DomainAware
{
    /**
     * Register domain awareness from configuration.
     *
     * @return void
     */
    public function registerDomainAwareness()
    {
        $this->app->resolving(RouteGenerator::class, static function (RouteGenerator $generator, Container $app) {
            $generator->setBaseUrl($app->make('config')->get('app.url'));
        });
    }
}
