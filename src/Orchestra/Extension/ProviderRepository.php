<?php namespace Orchestra\Extension;

use Illuminate\Container\Container;

class ProviderRepository
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * List of services.
     *
     * @var array
     */
    protected $services = array();

    /**
     * Construct a new finder.
     *
     * @param  \Illuminate\Container\Container  $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Load available service providers.
     *
     * @param  array    $services
     * @return void
     */
    public function provides(array $services)
    {
        foreach ($services as $service) {
            // Register service provider as a service for
            // Illuminate\Foundation\Application.
            $this->app->register($service);

            $this->services[] = $service;
        }
    }
}
