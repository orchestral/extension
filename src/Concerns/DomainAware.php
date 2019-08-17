<?php

namespace Orchestra\Extension\Concerns;

use Orchestra\Extension\RouteGenerator;
use Illuminate\Contracts\Container\Container;

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
