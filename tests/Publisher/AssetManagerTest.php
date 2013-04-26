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
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar')->andReturn(null);

		$stub = new AssetManager(array(), $publisher);
		$stub->publish('foo', 'bar');
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
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar/public')->andReturn(null);

		$stub = new AssetManager($app, $publisher);
		$stub->extension('foo');
	}
}
