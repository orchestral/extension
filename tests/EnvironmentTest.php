<?php namespace Orchestra\Extension\Tests;

use Mockery as m;
use Orchestra\Extension\Environment;

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
		$this->dispatcher = m::mock('\Orchestra\Extension\Dispatcher');
	}
	
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		unset($this->dispatcher);
		m::close();
	}

	/**
	 * Get data provider.
	 */
	protected function dataProvider()
	{
		return array(
			array(
				'path'    => '/foo/path/laravel/framework/',
				'config'  => array('foo' => 'bar', 'handles' => 'laravel'),
				'provide' => array('Laravel\FrameworkServiceProvider'),
			),
			array(
				'path'    => '/foo/app/',
				'config'  => array('foo' => 'bar'),
				'provide' => array(),
			),
		);
	}

	/**
	 * Test Orchestra\Extension\Environment::boot() method.
	 *
	 * @test
	 */
	public function testBootMethod()
	{
		$dispatcher = $this->dispatcher;
		$memory     = m::mock('Orchestra\Memory\Drivers\Driver');
		$request    = m::mock('Request');
		$session    = m::mock('Session');
		$app        = array(
			'orchestra.memory' => $memory,
			'request' => $request,
			'session' => $session,
		);

		list($options1, $options2) = $this->dataProvider();

		$extension = array('laravel/framework' => $options1, 'app' => $options2);

		$memory->shouldReceive('get')->once()->with('extensions.available', array())->andReturn($extension)
			->shouldReceive('get')->once()->with('extensions.active', array())->andReturn($extension);
		$dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturn(null)
			->shouldReceive('register')->once()->with('app', $options2)->andReturn(null)
			->shouldReceive('boot')->once()->andReturn(null);
		$request->shouldReceive('input')->once()->with('safe_mode')->andReturn('off');
		$session->shouldReceive('forget')->once()->with('orchestra.safemode')->andReturn(null);

		$stub = new Environment($app, $dispatcher);
		$stub->attach($memory);

		$this->assertEquals($memory, $stub->getMemoryProvider());
		
		$stub->boot();

		$this->assertEquals($options1['config'], $stub->option('laravel/framework', 'config'));
		$this->assertEquals('bad!', $stub->option('foobar/hello-world', 'config', 'bad!'));
		$this->assertTrue($stub->started('laravel/framework'));
		$this->assertFalse($stub->started('foobar/hello-world'));

	}

	/**
	 * Test Orchestra\Extension\Environment::route() method.
	 *
	 * @test
	 */
	public function testRouteMethod()
	{
		$dispatcher = $this->dispatcher;
		$memory     = m::mock('Orchestra\Memory\Drivers\Driver');
		$config     = m::mock('Config');
		$request    = m::mock('Request');
		$session    = m::mock('Session');
		$app        = array(
			'orchestra.memory' => $memory,
			'config' => $config,
			'request' => $request,
			'session' => $session,
		);

		list($options1, $options2) = $this->dataProvider();

		$extension = array('laravel/framework' => $options1, 'app' => $options2);

		$memory->shouldReceive('get')->once()->with('extensions.available', array())->andReturn($extension)
			->shouldReceive('get')->once()->with('extensions.active', array())->andReturn($extension);
		$dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturn(null)
			->shouldReceive('register')->once()->with('app', $options2)->andReturn(null)
			->shouldReceive('boot')->once()->andReturn(null);
		$config->shouldReceive('get')->with('orchestra/extension::handles.laravel/framework', '/')->andReturn('laravel');
		$request->shouldReceive('input')->once()->with('safe_mode')->andReturn(null);
		$session->shouldReceive('get')->once()->with('orchestra.safemode', 'off')->andReturn('off');

		$stub = new Environment($app, $dispatcher);
		$stub->attach($memory);
		$stub->boot();

		$this->assertEquals('laravel', $stub->route('laravel/framework', '/'));
	}

	/**
	 * Test Orchestra\Extension\Environment::isSafeMode() method.
	 *
	 * @test
	 */
	public function testIsSafeModeMethod()
	{
		$dispatcher = $this->dispatcher;
		$request    = m::mock('Request');
		$session    = m::mock('Session');
		$app        = array(
			'request' => $request,
			'session' => $session,
		);

		$stub = new Environment($app, $dispatcher);

		$request->shouldReceive('input')->once()->with('safe_mode')->andReturn('on');
		$session->shouldReceive('get')->once()->with('orchestra.safemode', 'off')->andReturn('off')
			->shouldReceive('put')->once()->with('orchestra.safemode', 'on')->andReturn(null);

		$this->assertTrue($stub->isSafeMode());
	}

	/**
	 * Test Orchestra\Extension\Environment::finish() method.
	 *
	 * @test
	 */
	public function testFinishMethod()
	{
		$dispatcher = $this->dispatcher;

		list($options1, $options2) = $this->dataProvider();

		$dispatcher->shouldReceive('finish')->with('laravel/framework', $options1)->andReturn(null)
			->shouldReceive('finish')->with('app', $options2)->andReturn(null);

		$stub = new Environment(array(), $dispatcher);

		$refl = new \ReflectionObject($stub);
		$extensions = $refl->getProperty('extensions');
		$extensions->setAccessible(true);
		$extensions->setValue($stub, array('laravel/framework' => $options1, 'app' => $options2));

		$stub->finish();
	}

	/**
	 * Test Orchestra\Extension\Environment::isAvailable() method.
	 *
	 * @test
	 */
	public function testIsAvailableMethod()
	{
		$memory = m::mock('Orchestra\Memory\Drivers\Driver');
		$app    = array('orchestra.memory' => $memory);

		$memory->shouldReceive('get')
				->once()->with('extensions.available.laravel/framework')->andReturn(array());

		$stub = new Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$this->assertTrue($stub->isAvailable('laravel/framework'));
	}

	/**
	 * Test Orchestra\Extension\Environment::active() method.
	 *
	 * @test
	 */
	public function testActivateMethod()
	{
		$dispatcher = $this->dispatcher;

		$memory   = m::mock('\Orchestra\Memory\Drivers\Driver');
		$migrator = m::mock('Migrator');
		$asset    = m::mock('Asset');
		$events   = m::mock('Event');
		$app      = array(
			'orchestra.memory' => $memory,
			'orchestra.publisher.migrate' => $migrator,
			'orchestra.publisher.asset' => $asset,
			'events' => $events,
		);

		$dispatcher->shouldReceive('register')->once()->with('laravel/framework', m::type('Array'))->andReturn(null)
			->shouldReceive('start')->once()->with('laravel/framework', m::type('Array'))->andReturn(null);
		$memory->shouldReceive('get')
				->once()->with('extensions.available', array())->andReturn(array('laravel/framework' => array()))
			->shouldReceive('get')
				->once()->with('extensions.active', array())->andReturn(array())
			->shouldReceive('put')
				->once()->with('extensions.active', array('laravel/framework' => array()))->andReturn(true);
		$migrator->shouldReceive('extension')
				->once()->with('laravel/framework')->andReturn(true);
		$asset->shouldReceive('extension')
				->once()->with('laravel/framework')->andReturn(true);
		$events->shouldReceive('fire')
				->once()->with('orchestra.publishing', array('laravel/framework'))->andReturn(true)
			->shouldReceive('fire')
				->once()->with('orchestra.publishing: laravel/framework')->andReturn(true);

		$stub = new Environment($app, $dispatcher);
		$stub->attach($memory);
		$stub->activate('laravel/framework');
	}

	/**
	 * Test Orchestra\Extension\Environment::deactive() method.
	 *
	 * @test
	 */
	public function testDeactivateMethod()
	{
		$memory = m::mock('Orchestra\Memory\Drivers\Driver');
		$app    = array('orchestra.memory' => $memory);

		$memory->shouldReceive('get')
				->once()->with('extensions.active', array())
				->andReturn(array('laravel/framework' => array(), 'daylerees/doc-reader' => array()))
			->shouldReceive('put')
				->once()->with('extensions.active', array('daylerees/doc-reader' => array()))
				->andReturn(true);

		$stub = new Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$stub->deactivate('laravel/framework');
	}

	/**
	 * Test Orchestra\Extension\Environment::activated() method.
	 *
	 * @test
	 */
	public function testActivatedMethod()
	{
		$memory = m::mock('Orchestra\Memory\Drivers\Driver');
		$app    = array('orchestra.memory' => $memory);

		$memory->shouldReceive('get')->once()->with('extensions.active.laravel/framework')->andReturn(array());

		$stub = new Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$this->assertTrue($stub->activated('laravel/framework'));
	}

	/**
	 * Test Orchestra\Extension\Environment::isWritableWithAsset() method.
	 *
	 * @test
	 */
	public function testIsWritableWithAssetMethod()
	{
		$memory = m::mock('Orchestra\Memory\Drivers\Driver');
		$files  = m::mock('Filesystem');
		$app    = array(
			'path.public' => '/var/orchestra',
			'orchestra.memory' => $memory,
			'files' => $files,
		);

		$memory->shouldReceive('get')->once()->with('extensions.available.foo.path', 'foo')->andReturn('foo')
			->shouldReceive('get')->once()->with('extensions.available.bar.path', 'bar')->andReturn('bar')
			->shouldReceive('get')->once()->with('extensions.available.foobar.path', 'foobar')->andReturn('foobar');
		$files->shouldReceive('isDirectory')->once()->with('foo/public')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('bar/public')->andReturn(true)
			->shouldReceive('isWritable')->once()->with('/var/orchestra/packages/bar')->andReturn(false)
			->shouldReceive('isDirectory')->once()->with('foobar/public')->andReturn(true)
			->shouldReceive('isWritable')->once()->with('/var/orchestra/packages/foobar')->andReturn(true);

		$stub = new Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$this->assertTrue($stub->isWritableWithAsset('foo'));
		$this->assertFalse($stub->isWritableWithAsset('bar'));
		$this->assertTrue($stub->isWritableWithAsset('foobar'));
	}

	/**
	 * Test Orchestra\Extension\Environment::detect() method.
	 *
	 * @test
	 */
	public function testDetectMethod()
	{
		$finder = m::mock('Finder');
		$memory = m::mock('Orchestra\Memory\Drivers\Driver');
		$app    = array(
			'orchestra.extension.finder' => $finder,
			'orchestra.memory' => $memory,
		);

		$finder->shouldReceive('detect')->once()->andReturn('foo');
		$memory->shouldReceive('put')->once()->with('extensions.available', 'foo')->andReturn('foobar');

		$stub = new Environment($app, $this->dispatcher);
		$stub->attach($memory);
		$this->assertEquals('foo', $stub->detect());
	}
}
