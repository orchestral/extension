<?php namespace Orchestra\Extension\Publisher;

use Exception;
use Illuminate\Foundation\AssetPublisher;
use Orchestra\Extension\FilePermissionException;

class AssetManager {

	/**
	 * Application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Migrator instance.
	 *
	 * @var Illuminate\Foundation\AssetPublisher
	 */
	protected $publisher = null;

	/**
	 * Construct a new instance.
	 *
	 * @access public
	 * @param  Illuminate\Foundation\Application    $app
	 * @param  Illuminate\Foundation\AssetPublisher $publisher
	 * @return void
	 */
	public function __construct($app, AssetPublisher $publisher)
	{
		$this->app       = $app;
		$this->publisher = $publisher;
	}

	/**
	 * Run migration for an extension or application.
	 *
	 * @access public	
	 * @param  string   $name
	 * @param  string   $destinationPath
	 * @return void
	 */
	public function publish($name, $destinationPath)
	{
		return $this->publisher->publish($name, $destinationPath);
	}

	/**
	 * Migration Orchestra Platform.
	 *
	 * @access public
	 * @return void
	 */
	public function foundation()
	{
		$path = rtrim($this->app['path.base'], '/').'/vendor/orchestra/foundation/src/public';

		if ( ! $this->app['files']->isDirectory($path)) return false;

		try 
		{
			return $this->publish('orchestra/foundation', $path);
		}
		catch (Exception $e)
		{
			throw new FilePermissionException("Unable to publish [{$path}].");
			return false;
		}
	}

	/**
	 * Migration Extension.
	 *
	 * @access public
	 * @return void
	 */
	public function extension($name)
	{
		$path = rtrim($this->app['orchestra.extension']->option($name, 'path'), '/').'/public';
		
		if ( ! $this->app['files']->isDirectory($path)) return false;

		try 
		{
			return $this->publish($name, $path);
		}
		catch (Exception $e)
		{
			throw new FilePermissionException("Unable to publish [{$path}].");
			return false;
		}
	}
}
