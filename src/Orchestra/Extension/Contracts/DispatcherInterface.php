<?php namespace Orchestra\Extension\Contracts;

interface DispatcherInterface {
	
	/**
	 * Register the extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function register($name, $options);

	/**
	 * Boot all extensions.
	 *
	 * @return void
	 */
	public function boot();

	/**
	 * Start the extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function start($name, $options);

	/**
	 * Shutdown an extension.
	 *
	 * @param  string   $name
	 * @param  array    $options
	 * @return void
	 */
	public function finish($name, $options);
}
