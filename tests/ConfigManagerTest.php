<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Container\Container;
use Orchestra\Extension\ConfigManager;

class ConfigManagerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test Orchestra\Extension\ConfigManager::map() method.
	 *
	 * @test
	 */
	public function testMapMethod()
	{
		$app    = new Container;
		$memory = m::mock('Memory');
		$config = m::mock('Config');
		
		$app['orchestra.memory'] = $memory;
		$app['config'] = $config;

		$memory->shouldReceive('make')
				->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extension_laravel/framework', array())->andReturn(array('foobar' => 'foobar is awesome'))
			->shouldReceive('put')
				->once()->with('extension_laravel/framework', array('foobar' => 'foobar is awesome', 'foo' => 'foobar'))->andReturn(true);
		$config->shouldReceive('set')
				->once()->with('laravel/framework::foobar', 'foobar is awesome')->andReturn(true)
			->shouldReceive('get')
				->once()->with('laravel/framework::foobar')->andReturn('foobar is awesome')
			->shouldReceive('get')
				->once()->with('laravel/framework::foo')->andReturn('foobar');

		$stub = new ConfigManager($app);

		$stub->map('laravel/framework', array(
			'foo'    => 'laravel/framework::foo',
			'foobar' => 'laravel/framework::foobar',
		));
	}
}
