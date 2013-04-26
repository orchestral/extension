<?php namespace Orchestra\Extension\Publisher;

use Illuminate\Foundation\AssetPublisher;

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
		$this->publisher->publish($name, $destinationPath);
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
		
		if ($this->app['files']->isDirectory($path)) $this->publish($name, $path);
	}
}
