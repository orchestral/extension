<?php namespace Orchestra\Extension;

class Dispatcher {
	
	/**
	 * Application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Provider instance.
	 *
	 * @var Orchestra\Extension\ProviderRepository
	 */
	protected $provider = null;

	/**
	 * List of extensions to be boot.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Construct a new Application instance.
	 *
	 * @param  \Illuminate\Foundation\Application       $app
	 * @param  \Orchestra\Extension\ProviderRepository  $provider
	 * @return void
	 */
	public function __construct($app, ProviderRepository $provider)
	{
		$this->app      = $app;
		$this->provider = $provider;
	}

	/**
	 * Register the extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function register($name, $options)
	{
		if ( ! is_string($name)) return ;

		$config = $options['config'];

		// Set the handles to orchestra/extension package config (if available).
		if (isset($config['handles']))
		{
			$this->app['config']->set("orchestra/extension::handles.{$name}", $config['handles']);
		}

		// Get available service providers from orchestra.json and register 
		// it to Laravel. In this case all service provider would be eager 
		// loaded since the application would require it from any action.
		$services = array_get($options, 'provide', array());
		! empty($services) and $this->provider->provides($services);

		// Register the extension so we can boot it later, this action is 
		// to allow all service providers to be registered first before we 
		// start the extension. An extension might be using another extension 
		// to work.
		$this->extensions[$name] = $options;
	}

	/**
	 * Boot all extensions.
	 *
	 * @return void
	 */
	public function boot()
	{
		foreach ($this->extensions as $name => $options)
		{
			$this->start($name, $options);
		}
	}

	/**
	 * Start the extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function start($name, $options)
	{
		// By now, extension should already exist as an extension. We should
		// be able start orchestra.php start file on each package.
		if ($this->app['files']->isFile($file = rtrim($options['path'], '/').'/src/orchestra.php'))
		{
			$this->app['files']->getRequire($file);
		}
		elseif ($this->app['files']->isFile($file = rtrim($options['path'], '/').'/orchestra.php'))
		{
			$this->app['files']->getRequire($file);
		}

		$this->fireEvent($name, $options, 'started');
	}

	/**
	 * Shutdown an extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function finish($name, $options)
	{
		$this->fireEvent($name, $options, 'done');
	}

	/**
	 * Fire events.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @param  string   $type
	 * @return void
	 */
	protected function fireEvent($name, $options, $type = 'started')
	{
		$this->app['events']->fire("extension.{$type}", array($name, $options));
		$this->app['events']->fire("extension.{$type}: {$name}", array($options));
	}
}
