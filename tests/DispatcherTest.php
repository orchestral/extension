<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Orchestra\Extension\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Provider instance.
	 *
	 * @var Orchestra\Extension\ProviderRepository
	 */
	private $provider = null;

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		$this->provider = m::mock('\Orchestra\Extension\ProviderRepository');
	}

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		unset($this->provider);
		m::close();
	}

	/**
	 * Test Orchestra\Extension\Dispatcher::start() method.
	 *
	 * @test
	 */
	public function testStartMethod()
	{
		$provider = $this->provider;
		$config   = m::mock('Config');
		$events   = m::mock('Event');
		$files    = m::mock('Filesystem');
		$finder   = m::mock('Finder');
		$app      = array(
			'config' => $config,
			'events' => $events,
			'files'  => $files,
			'orchestra.extension.finder' => $finder,
		);

		$options1 = array(
			'config'   => array('handles' => 'laravel'),
			'path'     => '/foo/path/laravel/framework/',
			'autoload' => array('hello.php'),
			'provide'  => array('Laravel\FrameworkServiceProvider'),
		);

		$options2 = array(
			'config' => array(),
			'path'   => '/foo/app/',
		);

		$config->shouldReceive('set')->once()
				->with('orchestra/extension::handles.laravel/framework', 'laravel')->andReturn(null);
		$events->shouldReceive('fire')->once()
				->with('extension.started: laravel/framework', array($options1))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.started', array('laravel/framework', $options1))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.booted: laravel/framework', array($options1))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.booted', array('laravel/framework', $options1))->andReturn(null);
		$files->shouldReceive('isFile')->once()
				->with('/foo/path/laravel/framework/hello.php')->andReturn(true)
			->shouldReceive('isFile')->once()
				->with('/foo/path/laravel/framework/src/orchestra.php')->andReturn(true)
			->shouldReceive('isFile')->once()
				->with('/foo/path/laravel/framework/orchestra.php')->andReturn(false)
			->shouldReceive('getRequire')->once()
				->with('/foo/path/laravel/framework/hello.php')->andReturn(true)
			->shouldReceive('getRequire')->once()
				->with('/foo/path/laravel/framework/src/orchestra.php')->andReturn(true);
		$provider->shouldReceive('provides')->once()
				->with(array('Laravel\FrameworkServiceProvider'))->andReturn(true);

		$events->shouldReceive('fire')->once()
				->with('extension.started: app', array($options2))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.started', array('app', $options2))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.booted: app', array($options2))->andReturn(null)
			->shouldReceive('fire')->once()
				->with('extension.booted', array('app', $options2))->andReturn(null);
		$files->shouldReceive('isFile')->once()
				->with('/foo/app/src/orchestra.php')->andReturn(false)
			->shouldReceive('isFile')->once()
				->with('/foo/app/orchestra.php')->andReturn(true)
			->shouldReceive('getRequire')->once()
				->with('/foo/app/orchestra.php')->andReturn(true);

		$finder->shouldReceive('resolveExtensionPath')->andReturnUsing(function ($p)
				{
					return $p;
				});

		$stub = new Dispatcher($app, $provider);

		$stub->register('laravel/framework', $options1);
		$stub->register('app', $options2);
		$stub->boot();
	}

	/**
	 * Test Orchestra\Extension\Dispatcher::finish() method.
	 *
	 * @test
	 */
	public function testFinishMethod()
	{
		$events = m::mock('Event');
		$app    = array('events' => $events);

		$events->shouldReceive('fire')
				->once()->with('extension.done: laravel/framework', array('foo'))->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.done', array('laravel/framework', 'foo'))->andReturn(null);

		$stub = new Dispatcher($app, $this->provider);
		$stub->finish('laravel/framework', 'foo');
	}
}
