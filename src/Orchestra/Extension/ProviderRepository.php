<?php namespace Orchestra\Extension;

class ProviderRepository {
	
	/**
	 * Application instance.
	 *
	 * @var \Illuminate\Foundation\Application
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
	 * @param  \Illuminate\Foundation\Application   $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Load available service providers.
	 *
	 * @param  array    $services
	 * @return void
	 */
	public function provides($services)
	{
		foreach ($services as $service)
		{
			$provider = (is_string($service) ? new $service($this->app) : $service);

			// During this process, Illuminate\Foundation\Application has 
			// been booted and it would ignore any of the deferred service 
			// provider that has a boot method. In this case we should 
			// manually run the boot method.
			$this->app->register($provider);
			$provider->boot();

			$this->services[] = $service;
		}
	}
}
