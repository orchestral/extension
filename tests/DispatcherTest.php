<?php namespace Orchestra\Extension\Tests;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\Dispatcher::start() method.
	 *
	 * @test
	 */
	public function testStartMethod()
	{
		$app  = array(
			'config' => ($config = \Mockery::mock('Config')),
			'events' => ($events = \Mockery::mock('Event')),
			'files'  => ($files  = \Mockery::mock('Filesystem')),
			'orchestra.extension.provider' => ($provider = \Mockery::mock('ProviderRepository')),
		);

		$options1 = array(
			'config'  => array('handles' => 'laravel'),
			'path'    => '/foo/path/laravel/framework/',
			'provide' => array('Laravel\FrameworkServiceProvider'),
		);

		$options2 = array(
			'config' => array(),
			'path'   => '/foo/app/',
		);

		$config->shouldReceive('set')
				->once()->with('orchestra/extension::handles.laravel/framework', 'laravel')->andReturn(null);

		$events->shouldReceive('fire')
				->once()->with('extension.started: laravel/framework', array($options1))->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.started', array('laravel/framework', $options1))->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.started: app', array($options2))->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.started', array('app', $options2))->andReturn(null);

		$files->shouldReceive('isFile')
				->once()->with('/foo/path/laravel/framework/src/orchestra.php')->andReturn(true)
			->shouldReceive('getRequire')
				->once()->with('/foo/path/laravel/framework/src/orchestra.php')->andReturn(true)
			->shouldReceive('isFile')
				->once()->with('/foo/app/src/orchestra.php')->andReturn(false)
			->shouldReceive('isFile')
				->once()->with('/foo/app/orchestra.php')->andReturn(true)
			->shouldReceive('getRequire')
				->once()->with('/foo/app/orchestra.php')->andReturn(true);

		$provider->shouldReceive('services')
				->once()->with(array('Laravel\FrameworkServiceProvider'))->andReturn(true);

		$stub = new \Orchestra\Extension\Dispatcher($app);

		$stub->start('laravel/framework', $options1);
		$stub->start('app', $options2);
	}

	/**
	 * Test Orchestra\Extension\Dispatcher::finish() method.
	 *
	 * @test
	 */
	public function testFinishMethod()
	{
		$app  = array(
			'events' => ($events = \Mockery::mock('Event')),
		);

		$events->shouldReceive('fire')
				->once()->with('extension.done: laravel/framework', array('foo'))->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.done', array('laravel/framework', 'foo'))->andReturn(null);

		$stub = new \Orchestra\Extension\Dispatcher($app);
		$stub->finish('laravel/framework', 'foo');
	}
}