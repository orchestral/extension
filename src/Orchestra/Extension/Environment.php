<?php namespace Orchestra\Extension;

use Exception;
use Orchestra\Memory\Abstractable\Container as AbstractableContainer;
use Orchestra\Memory\Drivers\Driver as MemoryDriver;
use Orchestra\Extension\Contracts\DebuggerInterface;
use Orchestra\Extension\Contracts\DispatcherInterface;

class Environment extends AbstractableContainer {

	/**
	 * Application instance.
	 *
	 * @var \Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Dispatcher instance.
	 *
	 * @var \Orchestra\Extension\Dispatcher
	 */
	protected $dispatcher = null;

	/**
	 * Debugger (safe mode) instance.
	 *
	 * @var \Orchestra\Extension\Debugger
	 */
	protected $debugger = null;

	/**
	 * Booted indicator.
	 *
	 * @var boolean
	 */
	protected $booted = false;

	/**
	 * List of extensions.
	 *
	 * @var array
	 */
	protected $extensions = array();

	/**
	 * Construct a new Application instance.
	 *
	 * @param  \Illuminate\Foundation\Application   $app
	 * @param  \Orchestra\Extension\Dispatcher      $dispatcher
	 * @param  \Orchestra\Extension\Debugger        $debugger
	 * @return void
	 */
	public function __construct($app, DispatcherInterface $dispatcher, DebuggerInterface $debugger)
	{
		$this->app        = $app;
		$this->dispatcher = $dispatcher;
		$this->debugger   = $debugger;
	}
	
	/**
	 * Boot active extensions.
	 *
	 * @return self
	 */
	public function boot()
	{
		// Avoid extension booting being called more than once.
		if ($this->booted) return $this;

		$this->booted = true;

		// Extension should be activated only if we're not running under 
		// safe mode (or debug mode). This is to ensure that developer have 
		// a way to disable broken extension without tempering the database.
		if ($this->debugger->check()) return $this;

		$this->registerActiveExtensions();

		// Boot are executed once all extension has been registered. This 
		// would allow extension to communicate with other extension 
		// without having to known the registration dependencies.
		$this->dispatcher->boot();

		return $this;
	}

	/**
	 * Shutdown all extensions.
	 *
	 * @return self
	 */
	public function finish()
	{
		foreach ($this->extensions as $name => $options)
		{
			$this->dispatcher->finish($name, $options);
		}

		$this->extensions = array();

		return $this;
	}

	/**
	 * Register all active extension to dispatcher.
	 *
	 * @return void
	 */
	protected function registerActiveExtensions()
	{
		$memory     = $this->memory;
		$availables = $memory->get('extensions.available', array());
		$actives    = $memory->get('extensions.active', array());

		// Loop all active extension and merge the configuration with 
		// available config. Extension registration is handled by dispatcher 
		// process due to complexity of extension boot process.
		foreach ($actives as $name => $options)
		{
			if (isset($availables[$name]))
			{
				$config = array_merge(
					(array) array_get($availables, "{$name}.config"), 
					(array) array_get($options, "config")
				);

				array_set($options, "config", $config);
				$this->extensions[$name] = $options;
				$this->dispatcher->register($name, $options);
			}
		}
	}

	/**
	 * Get extension route handle.
	 *
	 * @param  string   $name
	 * @param  string   $default
	 * @return string
	 */
	public function route($name, $default = '/')
	{
		// Boot the extension.
		$this->boot();

		// All route should be manage via `orchestra/extension::handles.{name}` 
		// config key, except for orchestra/foundation.
		$key = "orchestra/extension::handles.{$name}";

		return new RouteGenerator(
			$this->app['config']->get($key, $default), 
			$this->app['request']->root(),
			$this->app['request']->secure()
		);
	}

	/**
	 * Activate an extension.
	 *
	 * @param  string   $name
	 * @return void
	 */
	public function activate($name)
	{
		$memory     = $this->memory;
		$availables = $memory->get('extensions.available', array());
		$actives    = $memory->get('extensions.active', array());

		if (isset($availables[$name]))
		{
			// Append the activated extension to active extensions, and also
			// publish the extension (migrate the database and publish the
			// asset).
			$this->extensions[$name] = $actives[$name] = $availables[$name];
			$this->dispatcher->register($name, $actives[$name]);
			$this->publish($name);
		}

		$memory->put('extensions.active', $actives);
	}

	/**
	 * Deactivate an extension.
	 *
	 * @param  string   $name
	 * @return void
	 */
	public function deactivate($name)
	{
		$memory  = $this->memory;
		$current = $memory->get('extensions.active', array());
		$actives = array();

		foreach ($current as $extension => $config)
		{
			if ($extension === $name) continue;
		
			$actives[$extension] = $config;
		}

		$memory->put('extensions.active', $actives);
	}

	/**
	 * Publish an extension.
	 *
	 * @param  string
	 * @return void
	 */
	public function publish($name)
	{
		$this->app['orchestra.publisher.migrate']->extension($name);
		$this->app['orchestra.publisher.asset']->extension($name);

		$this->app['events']->fire("orchestra.publishing", array($name));
		$this->app['events']->fire("orchestra.publishing: {$name}");	
	}

	/**
	 * Check if extension is started.
	 *
	 * @param  string   $name
	 * @return boolean
	 */
	public function started($name)
	{
		return (array_key_exists($name, $this->extensions));
	}

	/**
	 * Get an option for a given extension.
	 *
	 * @param  string   $name
	 * @param  string   $option
	 * @param  mixed    $default
	 * @return mixed
	 */
	public function option($name, $option, $default = null)
	{
		if ( ! isset($this->extensions[$name]))
		{
			return value($default);
		}

		return array_get($this->extensions[$name], $option, $default);
	}

	/**
	 * Check whether an extension is available.
	 *
	 * @param  string   $name
	 * @return boolean
	 */
	public function available($name)
	{
		$memory = $this->memory;
		return (is_array($memory->get("extensions.available.{$name}")));
	}

	/**
	 * Check whether an extension is active.
	 *
	 * @param  string   $name
	 * @return boolean
	 */
	public function activated($name)
	{
		$memory = $this->memory;
		return (is_array($memory->get("extensions.active.{$name}")));
	}

	/**
	 * Check whether an extension has a writable public asset.
	 * 
	 * @param  string   $name
	 * @return boolean
	 */
	public function permission($name)
	{
		$finder   = $this->app['orchestra.extension.finder'];
		$memory   = $this->memory;
		$basePath = rtrim($memory->get("extensions.available.{$name}.path", $name), '/');
		$path     = $finder->resolveExtensionPath("{$basePath}/public");
		
		return $this->isWritableWithAsset($name, $path);
	}
	
	/**
	 * Check whether an extension has a writable public asset.
	 * 
	 * @param  string   $name
	 * @return boolean
	 */
	protected function isWritableWithAsset($name, $path)
	{
		$files      = $this->app['files'];
		$publicPath = $this->app['path.public'];
		$targetPath = "{$publicPath}/packages/{$name}";
		$isWritable = false;

		if (str_contains($name, '/') and ! $files->isDirectory($targetPath)) 
		{
			list($vendor) = explode('/', $name);
			$targetPath   = "{$publicPath}/packages/{$vendor}";
			$isWritable   = $files->isWritable($targetPath);
		}
		else
		{
			$isWritable = $files->isWritable($targetPath);
		}

		if ($files->isDirectory($path) and ! $isWritable) 
		{
			return false;
		}

		return true;
	}

	/**
	 * Detect all extensions.
	 *
	 * @return array
	 */
	public function detect()
	{
		$extensions = $this->app['orchestra.extension.finder']->detect();
		$this->memory->put('extensions.available', $extensions);

		return $extensions;
	}
}
