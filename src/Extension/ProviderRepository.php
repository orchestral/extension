<?php namespace Orchestra\Extension;

use Illuminate\Contracts\Foundation\Application;

class ProviderRepository
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * List of services.
     *
     * @var array
     */
    protected $services = array();

    /**
     * Construct a new finder.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(Application $app)
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
            $this->app->register($service);

            $this->services[] = $service;
        }
    }
}
