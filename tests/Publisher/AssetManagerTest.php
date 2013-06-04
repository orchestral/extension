<?php namespace Orchestra\Extension\Tests\Publisher;

use Mockery as m;
use Orchestra\Extension\Publisher\AssetManager;

class AssetManagerTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::publish() method.
	 *
	 * @test
	 */
	public function testPublishMethod()
	{
		$publisher = m::mock('\Illuminate\Foundation\AssetPublisher');
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar')->andReturn(true);

		$stub = new AssetManager(array(), $publisher);
		$this->assertTrue($stub->publish('foo', 'bar'));
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::extension() method.
	 *
	 * @test
	 */
	public function testExtensionMethod()
	{
		$files     = m::mock('Filesystem');
		$extension = m::mock('Extension');
		$publisher = m::mock('\Illuminate\Foundation\AssetPublisher');
		$app       = array(
			'files' => $files,
			'orchestra.extension' => $extension,
		);

		$files->shouldReceive('isDirectory')->once()->with('bar/public')->andReturn(true);
		$extension->shouldReceive('option')->once()->with('foo', 'path')->andReturn('bar');
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar/public')->andReturn(true);

		$stub = new AssetManager($app, $publisher);
		$this->assertTrue($stub->extension('foo'));
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::extension() method 
	 * throws exception.
	 *
	 * @expectedException \Orchestra\Extension\FilePermissionException
	 */
	public function testExtensionMethodThrowsException()
	{
		$files     = m::mock('Filesystem');
		$extension = m::mock('Extension');
		$publisher = m::mock('\Illuminate\Foundation\AssetPublisher');
		$app       = array(
			'files' => $files,
			'orchestra.extension' => $extension,
		);

		$files->shouldReceive('isDirectory')->once()->with('bar/public')->andReturn(true);
		$extension->shouldReceive('option')->once()->with('foo', 'path')->andReturn('bar');
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar/public')->andThrow('\Exception');

		$stub = new AssetManager($app, $publisher);
		$this->assertFalse($stub->extension('foo'));
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::foundation() method.
	 *
	 * @test
	 */
	public function testFoundationMethod()
	{
		$files     = m::mock('Filesystem');
		$publisher = m::mock('\Illuminate\Foundation\AssetPublisher');
		$app       = array(
			'files' => $files,
			'path.base' => '/foo/path/',
		);

		$files->shouldReceive('isDirectory')->once()
			->with('/foo/path/vendor/orchestra/foundation/src/public')->andReturn(true);
		$publisher->shouldReceive('publish')->once()
			->with('orchestra/foundation', '/foo/path/vendor/orchestra/foundation/src/public')->andReturn(true);
		
		$stub = new AssetManager($app, $publisher);
		$this->assertTrue($stub->foundation());
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::foundation() method 
	 * throws an exception.
	 *
	 * @expectedException Orchestra\Extension\FilePermissionException
	 */
	public function testFoundationMethodThrowsException()
	{
		$files     = m::mock('Filesystem');
		$publisher = m::mock('\Illuminate\Foundation\AssetPublisher');
		$app       = array(
			'files' => $files,
			'path.base' => '/foo/path/',
		);

		$files->shouldReceive('isDirectory')->once()
			->with('/foo/path/vendor/orchestra/foundation/src/public')->andReturn(true);
		$publisher->shouldReceive('publish')->once()
			->with('orchestra/foundation', '/foo/path/vendor/orchestra/foundation/src/public')->andThrow('Exception');
		
		$stub = new AssetManager($app, $publisher);
		$stub->foundation();
	}
}
