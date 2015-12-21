<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Orchestra\Extension\Factory;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events = null;

    /**
     * Dispatcher instance.
     *
     * @var \Orchestra\Extension\Dispatcher
     */
    protected $dispatcher = null;

    /**
     * Debugger (safe mode) instance.
     *
     * @var \Orchestra\Extension\SafeMode
     */
    protected $status = null;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->app = new Container();
        $this->events = m::mock('\Orchestra\Contracts\Events\Dispatcher');
        $this->dispatcher = m::mock('\Orchestra\Extension\Dispatcher');
        $this->status = m::mock('\Orchestra\Extension\StatusChecker');

        $this->app['events'] = $this->events;
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->app);
        unset($this->events);
        unset($this->dispatcher);
        unset($this->status);
        m::close();
    }

    /**
     * Get data provider.
     */
    protected function dataProvider()
    {
        return [
            [
                'path'    => '/foo/path/laravel/framework/',
                'config'  => ['foo' => 'bar', 'handles' => 'laravel'],
                'provide' => ['Laravel\FrameworkServiceProvider'],
            ],
            [
                'path'    => '/foo/app/',
                'config'  => ['foo' => 'bar'],
                'provide' => [],
            ],
        ];
    }

    /**
     * Test Orchestra\Extension\Factory::available() method.
     *
     * @test
     */
    public function testAvailableMethod()
    {
        $app = $this->app;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.memory'] = $memory;

        $memory->shouldReceive('get')
                ->once()->with('extensions.available.laravel/framework')->andReturn([]);

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->available('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Factory::active() method.
     *
     * @test
     */
    public function testActivateMethod()
    {
        $app = $this->app;
        $events = $this->events;
        $dispatcher = $this->dispatcher;

        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');
        $migrator = m::mock('\Orchestra\Publisher\MigrateManager');
        $asset = m::mock('\Orchestra\Publisher\AssetManager');

        $app['orchestra.memory'] = $memory;
        $app['orchestra.publisher.migrate'] = $migrator;
        $app['orchestra.publisher.asset'] = $asset;

        $dispatcher->shouldReceive('activating')->once()->with('laravel/framework', m::type('Array'))->andReturnNull();
        $memory->shouldReceive('get')->twice()
                ->with('extensions.available', [])->andReturn(['laravel/framework' => []])
            ->shouldReceive('get')->twice()->with('extensions.active', [])->andReturn([])
            ->shouldReceive('put')->once()
                ->with('extensions.active', ['laravel/framework' => []])->andReturn(true);
        $migrator->shouldReceive('extension')->once()->with('laravel/framework')->andReturn(true);
        $asset->shouldReceive('extension')->once()->with('laravel/framework')->andReturn(true);
        $events->shouldReceive('fire')->once()
                ->with('orchestra.publishing', ['laravel/framework'])->andReturn(true)
            ->shouldReceive('fire')->once()
                ->with('orchestra.publishing: laravel/framework')->andReturn(true);

        $stub = new Factory($app, $dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->activate('laravel/framework'));
        $this->assertFalse($stub->activate('laravel'));
    }

    /**
     * Test Orchestra\Extension\Factory::activated() method.
     *
     * @test
     */
    public function testActivatedMethod()
    {
        $app = $this->app;
        $dispatcher = $this->dispatcher;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.memory'] = $memory;

        $memory->shouldReceive('get')->once()->with('extensions.active.laravel/framework')->andReturn([]);

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->activated('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Factory::deactive() method.
     *
     * @test
     */
    public function testDeactivateMethod()
    {
        $app = $this->app;
        $dispatcher = $this->dispatcher;
        $app['orchestra.memory'] = $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $memory->shouldReceive('get')->twice()
                ->with('extensions.active', [])
                ->andReturn(['laravel/framework' => [], 'daylerees/doc-reader' => []])
            ->shouldReceive('put')->once()
                ->with('extensions.active', ['daylerees/doc-reader' => []])
                ->andReturn(true);
        $dispatcher->shouldReceive('deactivating')->once()->with('laravel/framework', m::type('Array'))->andReturnNull();

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->deactivate('laravel/framework'));
        $this->assertFalse($stub->deactivate('laravel'));
    }

    /**
     * Test Orchestra\Extension\Factory::boot() method.
     *
     * @test
     */
    public function testBootMethod()
    {
        $app = $this->app;
        $dispatcher = $this->dispatcher;
        $status = $this->status;
        $events = $this->events;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.memory'] = $memory;

        list($options1, $options2) = $this->dataProvider();

        $extension = ['laravel/framework' => $options1, 'app' => $options2];

        $events->shouldReceive('fire')->once()->with('orchestra.extension: booted')->andReturnNull();
        $memory->shouldReceive('get')->once()->with('extensions.available', [])->andReturn($extension)
            ->shouldReceive('get')->once()->with('extensions.active', [])->andReturn($extension);
        $dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('register')->once()->with('app', $options2)->andReturnNull()
            ->shouldReceive('boot')->once()->andReturnNull();
        $status->shouldReceive('is')->once()->with('safe')->andReturn(false);

        $stub = new Factory($app, $dispatcher, $status);
        $stub->attach($memory);

        $this->assertEquals($memory, $stub->getMemoryProvider());

        $stub->boot();

        $this->assertEquals($options1['config'], $stub->option('laravel/framework', 'config'));
        $this->assertEquals('bad!', $stub->option('foobar/hello-world', 'config', 'bad!'));
        $this->assertTrue($stub->started('laravel/framework'));
        $this->assertFalse($stub->started('foobar/hello-world'));
    }

    /**
     * Test Orchestra\Extension\Factory::detect() method.
     *
     * @test
     */
    public function testDetectMethod()
    {
        $app = $this->app;
        $events = $this->events;
        $finder = m::mock('\Orchestra\Contracts\Extension\Finder');
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.extension.finder'] = $finder;
        $app['orchestra.memory'] = $memory;

        $extensions = new Collection([
            'foo' => [
                'name'        => 'Foo',
                'description' => 'Foobar',
            ],
        ]);

        $events->shouldReceive('fire')->once()->with('orchestra.extension: detecting')->andReturnNull();
        $finder->shouldReceive('detect')->once()->andReturn($extensions);
        $memory->shouldReceive('put')->once()->with('extensions.available', ['foo' => ['name' => 'Foo']])->andReturnNull();

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertEquals($extensions, $stub->detect());
    }

    /**
     * Test Orchestra\Extension\Factory::finder() method.
     *
     * @test
     */
    public function testFinderMethod()
    {
        $app = $this->app;
        $finder = m::mock('\Orchestra\Contracts\Extension\Finder');

        $app['orchestra.extension.finder'] = $finder;

        $stub = new Factory($app, $this->dispatcher, $this->status);

        $this->assertEquals($finder, $stub->finder());
    }

    /**
     * Test Orchestra\Extension\Factory::finish() method.
     *
     * @test
     */
    public function testFinishMethod()
    {
        $dispatcher = $this->dispatcher;

        list($options1, $options2) = $this->dataProvider();

        $dispatcher->shouldReceive('finish')->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('finish')->with('app', $options2)->andReturnNull();

        $stub = new Factory($this->app, $dispatcher, $this->status);

        $refl = new \ReflectionObject($stub);
        $extensions = $refl->getProperty('extensions');
        $extensions->setAccessible(true);
        $extensions->setValue($stub, ['laravel/framework' => $options1, 'app' => $options2]);

        $stub->finish();
    }

    /**
     * Test Orchestra\Extension\Factory::permission() method.
     *
     * @test
     */
    public function testPermissionMethod()
    {
        $app = $this->app;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');
        $finder = m::mock('\Orchestra\Contracts\Extension\Finder');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');

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

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->permission('foo'));
        $this->assertFalse($stub->permission('bar'));
        $this->assertTrue($stub->permission('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Factory::register() method.
     *
     * @test
     */
    public function testRegisterMethod()
    {
        $app = $this->app;
        $finder = m::mock('\Orchestra\Contracts\Extension\Finder');
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.extension.finder'] = $finder;
        $app['orchestra.memory'] = $memory;

        $stub = new Factory($app, $this->dispatcher, $this->status);

        $finder->shouldReceive('registerExtension')->once()->with('hello', '/path/hello')->andReturn(true);

        $this->assertTrue($stub->register('hello', '/path/hello'));
    }

    /**
     * Test Orchestra\Extension\Factory::reset() method.
     *
     * @test
     */
    public function testResetMethod()
    {
        $app = $this->app;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');

        $app['orchestra.memory'] = $memory;
        $extension = ['config' => ['handles' => 'app']];

        $memory->shouldReceive('get')->once()
                ->with('extensions.available.laravel/framework', [])->andReturn($extension)
            ->shouldReceive('put')->once()
                ->with('extensions.active.laravel/framework', $extension)->andReturnNull()
            ->shouldReceive('has')->once()
                ->with('extension_laravel/framework')->andReturn(true)
            ->shouldReceive('put')->once()
                ->with('extension_laravel/framework', [])->andReturnNull();

        $stub = new Factory($app, $this->dispatcher, $this->status);
        $stub->attach($memory);
        $this->assertTrue($stub->reset('laravel/framework'));
    }

    /**
     * Test Orchestra\Extension\Factory::route() method.
     *
     * @test
     */
    public function testRouteMethod()
    {
        $app = $this->app;
        $dispatcher = $this->dispatcher;
        $status = $this->status;
        $events = $this->events;
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');
        $request = m::mock('\Illuminate\Http\Request');

        $app['orchestra.memory'] = $memory;
        $app['config'] = $config;
        $app['request'] = $request;

        list($options1, $options2) = $this->dataProvider();

        $extension = ['laravel/framework' => $options1, 'app' => $options2];

        $events->shouldReceive('fire')->once()->with('orchestra.extension: booted')->andReturnNull();
        $memory->shouldReceive('get')->once()->with('extensions.available', [])->andReturn($extension)
            ->shouldReceive('get')->once()->with('extensions.active', [])->andReturn($extension);
        $dispatcher->shouldReceive('register')->once()->with('laravel/framework', $options1)->andReturnNull()
            ->shouldReceive('register')->once()->with('app', $options2)->andReturnNull()
            ->shouldReceive('boot')->once()->andReturnNull();
        $status->shouldReceive('is')->once()->with('safe')->andReturn(false);
        $config->shouldReceive('get')->with('orchestra/extension::handles.laravel/framework', '/')->andReturn('laravel');
        $request->shouldReceive('root')->once()->andReturn('http://localhost')
                ->shouldReceive('secure')->twice()->andReturn(false);

        $stub = new Factory($app, $dispatcher, $status);
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
