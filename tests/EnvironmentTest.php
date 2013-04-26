<?php namespace Orchestra\Extension;

class EnvironmentTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Dispatcher instance.
	 *
	 * @var Orchestra\Extension\Dispatcher
	 */
	private $dispatcher = null;

	/**
	 * Setup the test environment.
	 */
	public function setUp()
	{
		$this->dispatcher = \Mockery::mock('\Orchestra\Extension\Dispatcher');
	}
	
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		unset($this->dispatcher);
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\Environment::boot() method.
	 *
	 * @test
	 */
	public function testBootMethod()
	{
		$app  = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$options1 = array(
			'path'    => '/foo/path/laravel/framework/',
			'config'  => array('foo' => 'bar'),
			'provide' => array('Laravel\FrameworkServiceProvider'),
		);

		$options2 = array(
			'path'    => '/foo/app/',
			'config'  => array('foo' => 'bar'),
			'provide' => array(),
		);

		$mock = array(
			'laravel/framework' => $options1,
			'app' => $options2,
		);

		$memory->shouldReceive('get')->once()->with('extensions.available', array())->andReturn($mock)
			->shouldReceive('get')->once()->with('extensions.active', array())->andReturn($mock);

		$dispatcher = $this->dispatcher;
		$dispatcher->shouldReceive('start')->with('laravel/framework', $options1)->andReturn(null)
			->shouldReceive('start')->with('app', $options2)->andReturn(null);

		$stub = new \Orchestra\Extension\Environment($app, $dispatcher);
		$stub->attach($memory);
		$stub->boot();

		$this->assertEquals(array('foo' => 'bar'), $stub->option('laravel/framework', 'config'));
		$this->assertEquals('bad!', $stub->option('foobar/hello-world', 'config', 'bad!'));
		$this->assertTrue($stub->started('laravel/framework'));
		$this->assertFalse($stub->started('foobar/hello-world'));
	}

	/**
	 * Test Orchestra\Extension\Environment::finish() method.
	 *
	 * @test
	 */
	public function testFinishMethod()
	{
		$options1 = array(
			'path'    => '/foo/path/laravel/framework/',
			'config'  => array('foo' => 'bar'),
			'provide' => array('Laravel\FrameworkServiceProvider'),
		);

		$options2 = array(
			'path'    => '/foo/app/',
			'config'  => array('foo' => 'bar'),
			'provide' => array(),
		);

		$dispatcher = $this->dispatcher;
		$dispatcher->shouldReceive('finish')->with('laravel/framework', $options1)->andReturn(null)
			->shouldReceive('finish')->with('app', $options2)->andReturn(null);


		$stub = new \Orchestra\Extension\Environment(array(), $dispatcher);

		$refl = new \ReflectionObject($stub);
		$extensions = $refl->getProperty('extensions');
		$extensions->setAccessible(true);
		$extensions->setValue($stub, array('laravel/framework' => $options1, 'app' => $options2));

		$stub->finish();
	}

	/**
	 * Test Orchestra\Extension\Environment::isActive() method.
	 *
	 * @test
	 */
	public function testIsAvailableMethod()
	{
		$app = array(
			'orchestra.memory' => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$memory->shouldReceive('get')
				->once()->with('extensions.available.laravel/framework')
				->andReturn(array());

		$stub = new \Orchestra\Extension\Environment($app, $this->dispatcher);
		$stub->attach($memory);
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
			'orchestra.memory' => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$memory->shouldReceive('get')
				->once()->with('extensions.available', array())
				->andReturn(array('laravel/framework' => array()))
			->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array())
			->shouldReceive('put')
				->once()->with('extensions.active', array('laravel/framework' => array()))
				->andReturn(true);

		$stub = new \Orchestra\Extension\Environment($app, $this->dispatcher);
		$stub->attach($memory);
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
			'orchestra.memory' => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$memory->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array('laravel/framework' => array(), 'daylerees/doc-reader' => array()))
			->shouldReceive('put')
				->once()->with('extensions.active', array('daylerees/doc-reader' => array()))
				->andReturn(true);

		$stub = new \Orchestra\Extension\Environment($app, $this->dispatcher);
		$stub->attach($memory);
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
			'orchestra.memory' => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$memory->shouldReceive('get')->once()->with('extensions.active.laravel/framework')->andReturn(array());

		$stub = new \Orchestra\Extension\Environment($app, $this->dispatcher);
		$stub->attach($memory);
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
			'orchestra.memory'           => ($memory = \Mockery::mock('Orchestra\Memory\Drivers\Driver')),
		);

		$finder->shouldReceive('detect')->once()->andReturn('foo');
		$memory->shouldReceive('put')->once()->with('extensions.available', 'foo')->andReturn('foobar');

		$stub = new \Orchestra\Extension\Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$this->assertEquals('foo', $stub->detect());
	}
}