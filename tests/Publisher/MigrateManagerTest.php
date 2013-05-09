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
		$app        = array(
			'migrator' => $migrator,
			'files' => $files,
			'orchestra.extension' => $extension,
		);

		$extension->shouldReceive('option')->once()->with('foo/bar', 'path')->andReturn('/foo/path/foo/bar/');
		$files->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/database/migrations/')->andReturn(true)
			->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/src/migrations/')->andReturn(false);
		$migrator->shouldReceive('getRepository')->once()->andReturn($repository)
			->shouldReceive('run')->once()->with('/foo/path/foo/bar/database/migrations/')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->once()->andReturn(true)
			->shouldReceive('createRepository')->never()->andReturn(null);

		$stub = new MigrateManager($app, $migrator);
		$stub->extension('foo/bar');
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
