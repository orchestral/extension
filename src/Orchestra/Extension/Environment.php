<?php namespace Orchestra\Extension;

use Exception;
use Orchestra\Memory\Drivers\Driver as MemoryDriver;

class Environment {

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
	 * Memory instance.
	 *
	 * @var \Orchestra\Memory\Drivers\Driver
	 */
	protected $memory = null;

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
	 * @return void
	 */
	public function __construct($app, Dispatcher $dispatcher)
	{
		$this->app        = $app;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Attach memory provider.
	 *
	 * @return self
	 */
	public function attach(MemoryDriver $memory)
	{
		$this->setMemoryProvider($memory);

		return $this;
	}

	/**
	 * Set memory provider.
	 *
	 * @param  \Orchestra\Memory\Drivers\Driver 
	 * @return self
	 */
	public function setMemoryProvider(MemoryDriver $memory)
	{
		$this->memory = $memory;

		return $this;
	}

	/**
	 * Set memory provider.
	 *
	 * @return \Orchestra\Memory\Drivers\Driver 
	 */
	public function getMemoryProvider()
	{
		return $this->memory;
	}
	
	/**
	 * Boot active extensions.
	 *
	 * @return self
	 */
	public function boot()
	{
		if ($this->booted) return $this;

		$this->booted = true;

		if ($this->isSafeMode()) return $this;

		$memory     = $this->memory;
		$availables = $memory->get('extensions.available', array());
		$actives    = $memory->get('extensions.active', array());

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

		return $this->app['config']->get($key, $default);
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
	 * Check whether an extension is available.
	 *
	 * @deprecated      To be removed in v2.2
	 * @param  string   $name
	 * @return boolean
	 * @see    self::available()
	 */
	public function isAvailable($name)
	{	
		return $this->available($name);
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
	 * Check whether an extension is active.
	 *
	 * @deprecated      To be removed in v2.2
	 * @param  string   $name
	 * @return boolean
	 * @see    self::activated()
	 */
	public function isActive($name)
	{
		return $this->activated($name);
	}

	/**
	 * Check whether an extension has a writable public asset.
	 * 
	 * @param  string   $name
	 * @return boolean
	 */
	public function permission($name)
	{
		$finder     = $this->app['orchestra.extension.finder'];
		$files      = $this->app['files'];
		$memory     = $this->memory;
		$publicPath = $this->app['path.public'];

		$basePath = rtrim($memory->get("extensions.available.{$name}.path", $name), '/');
		$path     = $finder->resolveExtensionPath("{$basePath}/public");

		if ($files->isDirectory($path) and ! $files->isWritable("{$publicPath}/packages/{$name}")) 
		{
			return false;
		}

		return true;
	}
	
	/**
	 * Check whether an extension has a writable public asset.
	 * 
	 * @deprecated      To be removed in v2.2
	 * @param  string   $name
	 * @return boolean
	 * @see    self::permission()
	 */
	public function isWritableWithAsset($name)
	{
		return $this->permission($name);
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

	/**
	 * Determine whether current request is in safe mode or not.
	 *
	 * @return boolean
	 */
	public function isSafeMode()
	{
		$input   = $this->app['request']->input('safe_mode');
		$session = $this->app['session'];

		if ($input == 'off')
		{
			$session->forget('orchestra.safemode');
			return false;
		}

		$mode = $session->get('orchestra.safemode', 'off');

		if ($input === 'on' and $mode !== $input)
		{
			$session->put('orchestra.safemode', $mode = $input);
		}

		return ($mode === 'on');
	}
}
