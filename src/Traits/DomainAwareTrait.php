<?php namespace Orchestra\Extension;

trait DomainAwareTrait
{
    /**
     * Register domain awareness from configuration.
     *
     * @return void
     */
    public function registerDomainAwareness()
    {
        $this->app->afterResolving(function (RouteGenerator $generator, $app) {
            $generator->setBaseUrl($app['config']->get('app.url'));
        });
    }
}
