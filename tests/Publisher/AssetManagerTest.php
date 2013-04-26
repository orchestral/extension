<?php namespace Orchestra\Extension\Tests\Publisher;

class AssetManagerTest extends \PHPUnit_Framework_TestCase {
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::publish() method.
	 *
	 * @test
	 */
	public function testPublishMethod()
	{
		$publisher = \Mockery::mock('\Illuminate\Foundation\AssetPublisher');
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar')->andReturn(null);

		$stub = new \Orchestra\Extension\Publisher\AssetManager(array(), $publisher);
		$stub->publish('foo', 'bar');
	}

	/**
	 * Test Orchestra\Extension\Publisher\AssetManager::extension() method.
	 *
	 * @test
	 */
	public function testExtensionMethod()
	{
		$app = array(
			'files' => $files = \Mockery::mock('Filesystem'),
			'orchestra.extension' => $extension = \Mockery::mock('Extension'),
		);

		$files->shouldReceive('isDirectory')->once()->with('bar/public')->andReturn(true);
		$extension->shouldReceive('option')->once()->with('foo', 'path')->andReturn('bar');

		$publisher = \Mockery::mock('\Illuminate\Foundation\AssetPublisher');
		$publisher->shouldReceive('publish')->once()->with('foo', 'bar/public')->andReturn(null);

		$stub = new \Orchestra\Extension\Publisher\AssetManager($app, $publisher);
		$stub->extension('foo');
	}
}