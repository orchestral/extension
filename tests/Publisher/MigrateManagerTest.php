<?php namespace Orchestra\Extension\Tests\Publisher;

class MigrateManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::run() method.
	 *
	 * @test
	 */
	public function testRunMethod()
	{
		$migrator = \Mockery::mock('\Illuminate\Database\Migrations\Migrator');
		$migrator->shouldReceive('getRepository')->once()->andReturn($repository = \Mockery::mock('Repository'))
			->shouldReceive('run')->once()->with('/foo/path/migrations')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->once()->andReturn(false)
			->shouldReceive('createRepository')->once()->andReturn(null);

		$stub = new \Orchestra\Extension\Publisher\MigrateManager(array(), $migrator);
		$stub->run('/foo/path/migrations');
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::extension() method.
	 *
	 * @test
	 */
	public function testExtensionMethod()
	{
		$app = array(
			'migrator' => $migrator = \Mockery::mock('\Illuminate\Database\Migrations\Migrator'),
			'files' => $files = \Mockery::mock('Filesystem'),
			'orchestra.extension' => $extension = \Mockery::mock('Extension'),
		);

		$extension->shouldReceive('option')->once()->with('foo/bar', 'path')->andReturn('/foo/path/foo/bar/');
		$files->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/migrations/')->andReturn(true)
			->shouldReceive('isDirectory')->once()->with('/foo/path/foo/bar/src/migrations/')->andReturn(false);
		$migrator->shouldReceive('getRepository')->once()->andReturn($repository = \Mockery::mock('Repository'))
			->shouldReceive('run')->once()->with('/foo/path/foo/bar/migrations/')->andReturn(null)
			->shouldReceive('run')->never()->with('/foo/path/foo/bar/migrations/')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->once()->andReturn(true)
			->shouldReceive('createRepository')->never()->andReturn(null);

		$stub = new \Orchestra\Extension\Publisher\MigrateManager($app, $migrator);
		$stub->extension('foo/bar');
	}

	/**
	 * Test Orchestra\Extension\Publisher\MigrateManager::foundation() method.
	 *
	 * @test
	 */
	public function testFoundationMethod()
	{
		$app = array(
			'migrator'  => $migrator = \Mockery::mock('\Illuminate\Database\Migrations\Migrator'),
			'path.base' => '/foo/path/',
		);

		$migrator->shouldReceive('getRepository')->twice()->andReturn($repository = \Mockery::mock('Repository'))
			->shouldReceive('run')->once()->with('/foo/path/vendor/orchestra/memory/src/migrations/')->andReturn(null)
			->shouldReceive('run')->once()->with('/foo/path/vendor/orchestra/auth/src/migrations/')->andReturn(null);
		$repository->shouldReceive('repositoryExists')->twice()->andReturn(true)
			->shouldReceive('createRepository')->never()->andReturn(null);

		$stub = new \Orchestra\Extension\Publisher\MigrateManager($app, $migrator);
		$stub->foundation();
	}
}