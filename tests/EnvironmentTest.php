<?php namespace Orchestra\Extension;

class EnvironmentTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\Environment::isActive() method.
	 *
	 * @test
	 */
	public function testIsAvailableMethod()
	{
		$app = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Memory')),
		);

		$memory->shouldReceive('make')->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extensions.available.laravel/framework')
				->andReturn(array());

		$stub = new \Orchestra\Extension\Environment($app);
		$this->assertTrue($stub->isAvailable('laravel/framework'));
	}

	/**
	 * Test Orchestra\Extension\Environment::active() method.
	 *
	 * @test
	 */
	public function testActiveMethod()
	{
		$app = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Memory')),
		);

		$memory->shouldReceive('make')
				->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extensions.available', array())
				->andReturn(array('laravel/framework' => array()))
			->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array())
			->shouldReceive('put')
				->once()->with('extensions.active', array('laravel/framework' => array()))
				->andReturn(true);

		$stub = new \Orchestra\Extension\Environment($app);
		$stub->activate('laravel/framework');
	}

	/**
	 * Test Orchestra\Extension\Environment::deactive() method.
	 *
	 * @test
	 */
	public function testDeactiveMethod()
	{
		$app = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Memory')),
		);

		$memory->shouldReceive('make')
				->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array('laravel/framework' => array(), 'daylerees/doc-reader' => array()))
			->shouldReceive('put')
				->once()->with('extensions.active', array('daylerees/doc-reader' => array()))
				->andReturn(true);

		$stub = new \Orchestra\Extension\Environment($app);
		$stub->deactivate('laravel/framework');
	}

	/**
	 * Test Orchestra\Extension\Environment::isActive() method.
	 *
	 * @test
	 */
	public function testIsActiveMethod()
	{
		$app = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Memory')),
		);

		$memory->shouldReceive('make')->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extensions.active.laravel/framework')
				->andReturn(array());

		$stub = new \Orchestra\Extension\Environment($app);
		$this->assertTrue($stub->isActive('laravel/framework'));
	}

	/**
	 * Test Orchestra\Extension\Environment::detect() method.
	 *
	 * @test
	 */
	public function testDetectMethod()
	{
		$app  = array(
			'orchestra.extension.finder' => ($finder = \Mockery::mock('Finder')),
			'orchestra.memory'           => ($memory = \Mockery::mock('Memory')),
		);

		$finder->shouldReceive('detect')
				->once()->andReturn('foo');

		$memory->shouldReceive('make')
				->once()->andReturn($memory)
			->shouldReceive('put')
				->once()->with('extensions.available', 'foo')
				->andReturn('foobar');

		$stub = new \Orchestra\Extension\Environment($app);
		$this->assertEquals('foo', $stub->detect());
	}

	/**
	 * Test Orchestra\Extension\Environment::load() method.
	 *
	 * @test
	 */
	public function testLoadMethod()
	{
		$app  = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Memory')),
			'events' => ($events = \Mockery::mock('Event')),
			'files'  => ($files  = \Mockery::mock('Filesystem')),
			'orchestra.extension.provider' => ($provider = \Mockery::mock('ProviderRepository')),
		);

		$memory->shouldReceive('make')
				->once()->andReturn($memory)
			->shouldReceive('get')
				->once()->with('extensions.available', array())
				->andReturn(array('laravel/framework' => array(
					'path'    => '/foo/path/laravel/framework/',
					'config'  => array('foo' => 'bar'),
					'service' => array('Laravel\FrameworkServiceProvider'),
				)))
			->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array('laravel/framework' => array()));

		$events->shouldReceive('fire')
				->once()->with('extension.started: laravel/framework')
				->andReturn(null)
			->shouldReceive('fire')
				->once()->with('extension.done: laravel/framework', \Mockery::any())
				->andReturn(null);

		$files->shouldReceive('isFile')
				->once()->with('/foo/path/laravel/framework/src/orchestra.php')
				->andReturn(true)
			->shouldReceive('getRequire')
				->once()->with('/foo/path/laravel/framework/src/orchestra.php')
				->andReturn(true);

		$provider->shouldReceive('services')
				->once()->with(array('Laravel\FrameworkServiceProvider'))
				->andReturn(true);

		$stub = new \Orchestra\Extension\Environment($app);
		$stub->load();

		$this->assertEquals(array('foo' => 'bar'), $stub->option('laravel/framework', 'config'));
		$this->assertEquals('bad!', $stub->option('foobar/hello-world', 'config', 'bad!'));
		$this->assertTrue($stub->started('laravel/framework'));
		$this->assertFalse($stub->started('foobar/hello-world'));

		$stub->shutdown();
	}
}