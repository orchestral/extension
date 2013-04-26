<?php namespace Orchestra\Extension\Publisher;

use Illuminate\Database\Migrations\Migrator;

class MigrateManager {

	/**
	 * Application instance.
	 *
	 * @var Illuminate\Foundation\Application
	 */
	protected $app = null;

	/**
	 * Migrator instance.
	 *
	 * @var Illuminate\Database\Migrations\Migrator
	 */
	protected $migrator = null;

	/**
	 * Construct a new instance.
	 *
	 * @access public
	 * @param  Illuminate\Foundation\Application        $app
	 * @param  Illuminate\Database\Migrations\Migrator  $migrator
	 * @return void
	 */
	public function __construct($app, Migrator $migrator)
	{
		$this->app      = $app;
		$this->migrator = $migrator;
	}

	/**
	 * Create migration repository if it's not available.
	 *
	 * @access protected
	 * @return void
	 */
	protected function createMigrationRepository()
	{
		$repository = $this->migrator->getRepository();

		if ( ! $repository->repositoryExists()) $repository->createRepository();
	}

	/**
	 * Run migration for an extension or application.
	 *
	 * @access public	
	 * @param  string   $path
	 * @return void
	 */
	public function run($path)
	{
		// We need to make sure migration table is available.
		$this->createMigrationRepository();

		$this->migrator->run($path);
	}

	/**
	 * Migration Extension.
	 *
	 * @access public
	 * @return void
	 */
	public function extension($name)
	{
		$basePath = rtrim($this->app['orchestra.extension']->option($name, 'path'), '/');
		$paths    = array("{$basePath}/migrations/", "{$basePath}/src/migrations/");

		foreach ($paths as $path)
		{
			if ($this->app['files']->isDirectory($path)) $this->run($path);
		}
	}

	/**
	 * Migration Orchestra Platform.
	 *
	 * @access public
	 * @return void
	 */
	public function foundation()
	{
		$basePath = rtrim($this->app['path.base'], '/');

		$this->run("{$basePath}/vendor/orchestra/memory/src/migrations/");
		$this->run("{$basePath}/vendor/orchestra/auth/src/migrations/");
	}
}