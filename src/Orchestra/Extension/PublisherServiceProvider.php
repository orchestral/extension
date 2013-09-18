<?php namespace Orchestra\Extension;

use Illuminate\Foundation\AssetPublisher;
use Illuminate\Support\ServiceProvider;

class PublisherServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerMigration();
		$this->registerAssetPublisher();
		$this->registerExtensionCommand();
	}

	/**
	 * Register the service provider for Orchestra Platform migrator.
	 *
	 * @return void
	 */
	protected function registerMigration()
	{	
		$this->app['orchestra.publisher.migrate'] = $this->app->share(function ($app)
		{
			// In order to use migration, we need to boot 'migration.repository' 
			// instance.
			$app['migration.repository'];
			
			return new Publisher\MigrateManager($app, $app['migrator']);
		});
	}

	/**
	 * Register the service provider for Orchestra Platform asset publisher.
	 *
	 * @return void
	 */
	protected function registerAssetPublisher()
	{
		$this->app['orchestra.publisher.asset'] = $this->app->share(function ($app)
		{
			$publisher = new AssetPublisher($app['files'], $app['path.public']);
			return new Publisher\AssetManager($app, $publisher);
		});
	}

	/**
	 * Register the service provider for Extension commands.
	 *
	 * @return void
	 */
	protected function registerExtensionCommand()
	{
		$this->app['orchestra.commands.extension'] = $this->app->share(function()
		{
			return new Console\ExtensionCommand;
		});

		$this->commands('orchestra.commands.extension');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array(
			'orchestra.publisher.migrate', 
			'orchestra.publisher.asset',
			'orchestra.commands.extension',
		);
	}
}
