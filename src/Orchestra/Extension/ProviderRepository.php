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
	 * Load available services.
	 *
	 * @access public
	 * @param  array    $providers
	 * @return void
	 */
	public function services($providers)
	{
		foreach ($providers as $provider)
		{
			$this->app->register($provider);
		}
	}

}