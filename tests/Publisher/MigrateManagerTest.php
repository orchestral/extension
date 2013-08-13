<?php namespace Orchestra\Extension\Tests\Publisher;

use Mockery as m;
use Orchestra\Extension\Publisher\MigrateManager;

class MigrateManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::run() method.
	 *
	 * @test
	 */
	public function testRunMethod()
	{
		$migrator   = m::mock('\Illuminate\Database\Migrations\Migrator');
		$repository = m::mock('Repository');

		$migrator->shouldReceive('getRepository')->once()->andReturn($repository)
			->shouldReceive('run')->once()->with('/foo/path/migrations')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->once()->andReturn(false)
			->shouldReceive('createRepository')->once()->andReturn(null);

		$stub = new MigrateManager(array(), $migrator);
		$stub->run('/foo/path/migrations');
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::extension() method.
	 *
	 * @test
	 */
	public function testExtensionMethod()
	{
		$migrator   = m::mock('\Illuminate\Database\Migrations\Migrator');
		$files      = m::mock('Filesystem');
		$extension  = m::mock('Extension');
		$repository = m::mock('Repository');
		$finder     = m::mock('Finder');
		$app        = array(
			'migrator' => $migrator,
			'files' => $files,
			'orchestra.extension' => $extension,
			'orchestra.extension.finder' => $finder,
		);

		$extension->shouldReceive('option')->once()->with('foo/bar', 'path')->andReturn('/foo/path/foo/bar/')
			->shouldReceive('option')->once()->with('foo/bar', 'source-path')->andReturn('/foo/app/foo/bar/')
			->shouldReceive('option')->once()->with('laravel/framework', 'path')->andReturn('/foo/path/laravel/framework/')
			->shouldReceive('option')->once()->with('laravel/framework', 'source-path')->andReturn('/foo/path/laravel/framework/');
		$finder->shouldReceive('resolveExtensionPath')->once()->with('/foo/path/foo/bar')->andReturn('/foo/path/foo/bar')
			->shouldReceive('resolveExtensionPath')->once()->with('/foo/app/foo/bar')->andReturn('/foo/app/foo/bar')
			->shouldReceive('resolveExtensionPath')->twice()->with('/foo/path/laravel/framework')->andReturn('/foo/path/laravel/framework');
		$files->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/database/migrations/')->andReturn(true)
			->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/src/migrations/')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/database/migrations/')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('/foo/app/foo/bar/src/migrations/')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/database/migrations/')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('/foo/path/laravel/framework/src/migrations/')->andReturn(false);
		$migrator->shouldReceive('getRepository')->once()->andReturn($repository)
			->shouldReceive('run')->once()->with('/foo/path/foo/bar/database/migrations/')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->once()->andReturn(true)
			->shouldReceive('createRepository')->never()->andReturn(null);

		$stub = new MigrateManager($app, $migrator);
		$stub->extension('foo/bar');
		$stub->extension('laravel/framework');
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::foundation() method.
	 *
	 * @test
	 */
	public function testFoundationMethod()
	{
		$migrator   = m::mock('\Illuminate\Database\Migrations\Migrator');
		$repository = m::mock('Repository');
		$app        = array(
			'migrator'  => $migrator,
			'path.base' => '/foo/path/',
		);

		$migrator->shouldReceive('getRepository')->twice()->andReturn($repository)
			->shouldReceive('run')->once()->with('/foo/path/vendor/orchestra/memory/src/migrations/')->andReturn(null)
			->shouldReceive('run')->once()->with('/foo/path/vendor/orchestra/auth/src/migrations/')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->twice()->andReturn(true)
			->shouldReceive('createRepository')->never()->andReturn(null);

		$stub = new MigrateManager($app, $migrator);
		$stub->foundation();
	}
}
