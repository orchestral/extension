<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Orchestra\Extension\Environment;

class EnvironmentTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Dispatcher instance.
     *
     * @var \Orchestra\Extension\Dispatcher
     */
    protected $dispatcher = null;

    /**
     * Debugger (safe mode) instance.
     *
     * @var \Orchestra\Extension\Debugger
     */
    protected $debugger = null;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->app        = new Container;
        $this->dispatcher = m::mock('\Orchestra\Extension\Dispatcher');
        $this->debugger   = m::mock('\Orchestra\Extension\Debugger');
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->app);
        unset($this->dispatcher);
        unset($this->debugger);
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
     * Test Orchestra\Extension\Environment::available() method.
     *
     * @test
     */
    public function testAvailableMethod()
    {
        $app    = $this->app;
        $memory = m::mock('\Orchestra\Memory\Provider');

        $app['orchestra.memory'] = $memory;

        $memory->shouldReceive('get')
                ->once()->with('extensions.available.laravel/framework')->andReturn(array());

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->available('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Environment::active() method.
     *
     * @test
     */
    public function testActivateMethod()
    {
        $app        = $this->app;
        $dispatcher = $this->dispatcher;

        $memory   = m::mock('\Orchestra\Memory\Provider');
        $migrator = m::mock('\Orchestra\Extension\Publisher\MigrateManager[extension]');
        $asset    = m::mock('\Orchestra\Extension\Publisher\AssetManager[extension]');
        $events   = m::mock('\Illuminate\Events\Dispatcher');

        $app['orchestra.memory'] = $memory;
        $app['orchestra.publisher.migrate'] = $migrator;
        $app['orchestra.publisher.asset'] = $asset;
        $app['events'] = $events;

        $dispatcher->shouldReceive('register')->once()
                ->with('laravel/framework', m::type('Array'))->andReturnNull();
        $memory->shouldReceive('get')->twice()
                ->with('extensions.available', array())->andReturn(array('laravel/framework' => array()))
            ->shouldReceive('get')->twice()
                ->with('extensions.active', array())->andReturn(array())
            ->shouldReceive('put')->once()
                ->with('extensions.active', array('laravel/framework' => array()))->andReturn(true);
        $migrator->shouldReceive('extension')->once()
                ->with('laravel/framework')->andReturn(true);
        $asset->shouldReceive('extension')->once()
                ->with('laravel/framework')->andReturn(true);
        $events->shouldReceive('fire')->once()
                ->with('orchestra.publishing', array('laravel/framework'))->andReturn(true)
            ->shouldReceive('fire')->once()
                ->with('orchestra.publishing: laravel/framework')->andReturn(true);

        $stub = new Environment($app, $dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->activate('laravel/framework'));
        $this->assertFalse($stub->activate('laravel'));
    }

    /**
     * Test Orchestra\Extension\Environment::activated() method.
     *
     * @test
     */
    public function testActivatedMethod()
    {
        $app    = $this->app;
        $memory = m::mock('\Orchestra\Memory\Provider');
        $app['orchestra.memory'] = $memory;

        $memory->shouldReceive('get')->once()->with('extensions.active.laravel/framework')->andReturn(array());

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->activated('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Environment::deactive() method.
     *
     * @test
     */
    public function testDeactivateMethod()
    {
        $app    = $this->app;
        $memory = m::mock('\Orchestra\Memory\Provider');
        $app['orchestra.memory'] = $memory;

        $memory->shouldReceive('get')->twice()
                ->with('extensions.active', array())
                ->andReturn(array('laravel/framework' => array(), 'daylerees/doc-reader' => array()))
            ->shouldReceive('put')->once()
                ->with('extensions.active', array('daylerees/doc-reader' => array()))
                ->andReturn(true);

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->deactivate('laravel/framework'));
        $this->assertFalse($stub->deactivate('laravel'));
    }


    /**
     * Test Orchestra\Extension\Environment::boot() method.
     *
     * @test
     */
    public function testBootMethod()
    {
        $app        = $this->app;
        $dispatcher = $this->dispatcher;
        $debugger   = $this->debugger;
        $memory     = m::mock('\Orchestra\Memory\Provider');

        $app['orchestra.memory'] = $memory;

        list($options1, $options2) = $this->dataProvider();

        $extension = array('laravel/framework' => $options1, 'app' => $options2);

        $memory->shouldReceive('get')->once()->with('extensions.available', array())->andReturn($extension)
            ->shouldReceive('get')->once()->with('extensions.active', array())->andReturn($extension);
        $dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('register')->once()->with('app', $options2)->andReturnNull()
            ->shouldReceive('boot')->once()->andReturnNull();
        $debugger->shouldReceive('check')->once()->andReturn(false);

        $stub = new Environment($app, $dispatcher, $debugger);
        $stub->attach($memory);

        $this->assertEquals($memory, $stub->getMemoryProvider());

        $stub->boot();

        $this->assertEquals($options1['config'], $stub->option('laravel/framework', 'config'));
        $this->assertEquals('bad!', $stub->option('foobar/hello-world', 'config', 'bad!'));
        $this->assertTrue($stub->started('laravel/framework'));
        $this->assertFalse($stub->started('foobar/hello-world'));
    }

    /**
     * Test Orchestra\Extension\Environment::detect() method.
     *
     * @test
     */
    public function testDetectMethod()
    {
        $app    = $this->app;
        $finder = m::mock('\Orchestra\Extension\Finder');
        $memory = m::mock('\Orchestra\Memory\Provider');

        $app['orchestra.extension.finder'] = $finder;
        $app['orchestra.memory'] = $memory;

        $extensions = new Collection(array('foo'));

        $finder->shouldReceive('detect')->once()->andReturn($extensions);
        $memory->shouldReceive('put')->once()->with('extensions.available', array('foo'))->andReturn('foobar');

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertEquals($extensions, $stub->detect());
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

        $dispatcher->shouldReceive('finish')->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('finish')->with('app', $options2)->andReturnNull();

        $stub = new Environment($this->app, $dispatcher, $this->debugger);

        $refl = new \ReflectionObject($stub);
        $extensions = $refl->getProperty('extensions');
        $extensions->setAccessible(true);
        $extensions->setValue($stub, array('laravel/framework' => $options1, 'app' => $options2));

        $stub->finish();
    }

    /**
     * Test Orchestra\Extension\Environment::permission() method.
     *
     * @test
     */
    public function testPermissionMethod()
    {
        $app    = $this->app;
        $memory = m::mock('\Orchestra\Memory\Provider');
        $finder = m::mock('Finder');
        $files  = m::mock('Filesystem');

        $app['path.public'] = '/var/orchestra';
        $app['orchestra.memory'] = $memory;
        $app['files'] = $files;
        $app['orchestra.extension.finder'] = $finder;

        $memory->shouldReceive('get')->once()->with('extensions.available.foo.path', 'foo')->andReturn('foo')
            ->shouldReceive('get')->once()->with('extensions.available.bar.path', 'bar')->andReturn('bar')
            ->shouldReceive('get')->once()->with('extensions.available.laravel/framework.path', 'laravel/framework')->andReturn('laravel/framework');
        $finder->shouldReceive('resolveExtensionPath')->once()->with('foo/public')->andReturn('foo/public')
            ->shouldReceive('resolveExtensionPath')->once()->with('bar/public')->andReturn('bar/public')
            ->shouldReceive('resolveExtensionPath')->once()->with('laravel/framework/public')->andReturn('laravel/framework/public');
        $files->shouldReceive('isDirectory')->once()->with('foo/public')->andReturn(false)
            ->shouldReceive('isWritable')->once()->with('/var/orchestra/packages/foo')->andReturn(false)
            ->shouldReceive('isDirectory')->once()->with('bar/public')->andReturn(true)
            ->shouldReceive('isWritable')->once()->with('/var/orchestra/packages/bar')->andReturn(false)
            ->shouldReceive('isDirectory')->once()->with('laravel/framework/public')->andReturn(true)
            ->shouldReceive('isDirectory')->once()->with('/var/orchestra/packages/laravel/framework')->andReturn(false)
            ->shouldReceive('isWritable')->once()->with('/var/orchestra/packages/laravel')->andReturn(true);

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->permission('foo'));
        $this->assertFalse($stub->permission('bar'));
        $this->assertTrue($stub->permission('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Environment::reset() method.
     *
     * @test
     */
    public function testResetMethod()
    {
        $app    = $this->app;
        $memory = m::mock('\Orchestra\Memory\Provider');

        $app['orchestra.memory'] = $memory;
        $extension = array('config' => array('handles' => 'app'));

        $memory->shouldReceive('get')->once()
                ->with('extensions.available.laravel/framework', array())->andReturn($extension)
            ->shouldReceive('put')->once()
                ->with('extensions.active.laravel/framework', $extension)->andReturnNull()
            ->shouldReceive('has')->once()
                ->with('extension_laravel/framework')->andReturn(true)
            ->shouldReceive('put')->once()
                ->with('extension_laravel/framework', array())->andReturnNull();

        $stub = new Environment($app, $this->dispatcher, $this->debugger);
        $stub->attach($memory);
        $this->assertTrue($stub->reset('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Environment::route() method.
     *
     * @test
     */
    public function testRouteMethod()
    {
        $app        = $this->app;
        $dispatcher = $this->dispatcher;
        $debugger   = $this->debugger;
        $memory     = m::mock('\Orchestra\Memory\Provider');
        $config     = m::mock('\Illuminate\Config\Repository');
        $request    = m::mock('\Illuminate\Http\Request');

        $app['orchestra.memory'] = $memory;
        $app['config'] = $config;
        $app['request'] = $request;

        list($options1, $options2) = $this->dataProvider();

        $extension = array('laravel/framework' => $options1, 'app' => $options2);

        $memory->shouldReceive('get')->once()->with('extensions.available', array())->andReturn($extension)
            ->shouldReceive('get')->once()->with('extensions.active', array())->andReturn($extension);
        $dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('register')->once()->with('app', $options2)->andReturnNull()
            ->shouldReceive('boot')->once()->andReturnNull();
        $debugger->shouldReceive('check')->once()->andReturn(false);
        $config->shouldReceive('get')->with('orchestra/extension::handles.laravel/framework', '/')->andReturn('laravel');
        $request->shouldReceive('root')->once()->andReturn('http://localhost')
                ->shouldReceive('secure')->twice()->andReturn(false);

        $stub = new Environment($app, $dispatcher, $debugger);
        $stub->attach($memory);
        $stub->boot();

        $output = $stub->route('laravel/framework', '/');

        $this->assertInstanceOf('\Orchestra\Extension\RouteGenerator', $output);
        $this->assertEquals('laravel', $output);
        $this->assertEquals(null, $output->domain());
        $this->assertEquals('localhost', $output->domain(true));
        $this->assertEquals('laravel', $output->prefix());
        $this->assertEquals('laravel', $output->prefix(true));
        $this->assertEquals('http://localhost/laravel', $output->root());
        $this->assertEquals('http://localhost/laravel/hello', $output->to('hello'));
    }
}
