<?php namespace Orchestra\Extension;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Contracts\Foundation\DeferrableServiceContainer;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcherContract;

class ProviderRepository
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * List of services.
     *
     * @var array
     */
    protected $services = [];

    /**
     * List of deferred services.
     *
     * @var array
     */
    protected $deferredServices = [];

    /**
     * Construct a new finder.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     */
    public function __construct(Application $app, EventDispatcherContract $events)
    {
        $this->app    = $app;
        $this->events = $events;
    }

    /**
     * Load available service providers.
     *
     * @param  array  $services
     *
     * @return void
     */
    public function provides(array $services)
    {
        foreach ($services as $provider) {
            $instance = $this->app->resolveProviderClass($provider);

            if ($instance->isDeferred() && $this->app instanceof DeferrableServiceContainer) {
                $this->registerDeferredServiceProvider($instance, $provider);
            } else {
                $this->registerEagerServiceProvider($instance);
            }

            $this->services[] = $provider;
        }

        $this->registerDeferredServiceProviders();
    }

    /**
     * Register all deferred service providers.
     *
     * @return void
     */
    protected function registerDeferredServiceProviders()
    {
        if (! $this->app instanceof DeferrableServiceContainer) {
            return ;
        }

        $this->app->setDeferredServices(array_merge(
            $this->app->getDeferredServices(),
            $this->deferredServices
        ));
    }

    /**
     * Register deferred service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $instance
     * @param  string  $provider
     *
     * @return void
     */
    protected function registerDeferredServiceProvider(ServiceProvider $instance, $provider)
    {
        $when = $instance->when();

        foreach ($when as $listen) {
            $this->events->listen($listen, function () use ($instance) {
                $this->app->register($instance);
            });
        }

        foreach ($instance->provides() as $service) {
            $this->deferredServices[$service] = $provider;
        }
    }

    /**
     * Register eager service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $instance
     *
     * @return void
     */
    protected function registerEagerServiceProvider(ServiceProvider $instance)
    {
        $this->app->register($instance);
    }
}
