<?php namespace Orchestra\Extension;

class ProviderRepository {
	
	/**
	 * Application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Construct a new finder.
	 *
	 * @access public
	 * @param  Illuminate\Foundation\Application    $app
	 * @return void
	 */
	public function __construct($app)
	{
		$this->app = $app;
	}

	/**
	 * Load available service providers.
	 *
	 * @access public
	 * @param  array    $services
	 * @return void
	 */
	public function provides($services)
	{
		foreach ($services as $service)
		{
			$this->app->register($service);
		}
	}

}