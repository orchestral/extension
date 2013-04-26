<?php namespace Orchestra\Extension;

class Dispatcher {
	
	/**
	 * Application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Provider instance.
	 *
	 * @var Orchestra\Extension\ProviderRepository
	 */
	protected $provider = null;

	/**
	 * Construct a new Application instance.
	 *
	 * @access public
	 * @param  Illuminate\Foundation\Application        $app
	 * @param  Orchestra\Extension\ProviderRepository   $provider
	 * @return void
	 */
	public function __construct($app, ProviderRepository $provider)
	{
		$this->app      = $app;
		$this->provider = $provider;
	}

	/**
	 * Start the extension.
	 *
	 * @access public	
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function start($name, $options)
	{
		if ( ! is_string($name)) return ;

		$config = $options['config'];

		if (isset($config['handles']))
		{
			$this->app['config']->set("orchestra/extension::handles.{$name}", $config['handles']);
		}

		$services = array_get($options, 'provide', array());

		// by now, extension should already exist as an extension. We should
		// be able start orchestra.php start file on each package.
		if ($this->app['files']->isFile($file = rtrim($options['path'], '/').'/src/orchestra.php'))
		{
			$this->app['files']->getRequire($file);
		}
		elseif ($this->app['files']->isFile($file = rtrim($options['path'], '/').'/orchestra.php'))
		{
			$this->app['files']->getRequire($file);
		}

		! empty($services) and $this->provider->provides($services);
		
		$this->fireEvent($name, $options, 'started');
	}

	/**
	 * Shutdown an extension.
	 *
	 * @access public
	 * @return void
	 */
	public function finish($name, $options)
	{
		$this->fireEvent($name, $options, 'done');
	}

	/**
	 * Fire events.
	 *
	 * @access protected
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
