<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Orchestra\Extension\Dispatcher;

class DispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Get mocked Orchestra\Extension\ProviderRepository
     *
     * @return \Orchestra\Extension\ProviderRepository
     */
    protected function getProvider()
    {
        return m::mock('\Orchestra\Extension\ProviderRepository', array(
            m::mock('\Illuminate\Contracts\Foundation\Application')
        ));
    }

    /**
     * Test Orchestra\Extension\Dispatcher::start() method.
     *
     * @test
     */
    public function testStartMethod()
    {
        $provider = $this->getProvider();
        $config   = m::mock('\Illuminate\Contracts\Config\Repository');
        $event    = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files    = m::mock('\Illuminate\Filesystem\Filesystem');
        $finder   = m::mock('\Orchestra\Extension\Finder');

        $options1 = array(
            'config'      => array('handles' => 'laravel'),
            'path'        => '/foo/app/laravel/framework/',
            'source-path' => '/foo/app',
            'autoload'    => array(
                'source-path::hello.php',
                'start.php',
            ),
            'provide'     => array('Laravel\FrameworkServiceProvider'),
        );

        $options2 = array(
            'config' => array(),
            'path'   => '/foo/app/',
        );

        $config->shouldReceive('set')->once()
                ->with('orchestra/extension::handles.laravel/framework', 'laravel')->andReturnNull();
        $event->shouldReceive('fire')->once()
                ->with('extension.started: laravel/framework', array($options1))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.started', array('laravel/framework', $options1))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted: laravel/framework', array($options1))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted', array('laravel/framework', $options1))->andReturnNull();
        $files->shouldReceive('isFile')->once()->with('/foo/app/hello.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/start.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/src/orchestra.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/orchestra.php')->andReturn(false)
            ->shouldReceive('getRequire')->once()->with('/foo/app/hello.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()->with('/foo/app/start.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()->with('/foo/app/src/orchestra.php')->andReturn(true);
        $provider->shouldReceive('provides')->once()
                ->with(array('Laravel\FrameworkServiceProvider'))->andReturn(true);

        $event->shouldReceive('fire')->once()
                ->with('extension.started: app', array($options2))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.started', array('app', $options2))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted: app', array($options2))->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted', array('app', $options2))->andReturnNull();
        $files->shouldReceive('isFile')->once()
                ->with('/foo/app/src/orchestra.php')->andReturn(false)
            ->shouldReceive('isFile')->once()
                ->with('/foo/app/orchestra.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()
                ->with('/foo/app/orchestra.php')->andReturn(true);

        $finder->shouldReceive('resolveExtensionPath')->andReturnUsing(function ($p) {
            return $p;
        });

        $stub = new Dispatcher($config, $event, $files, $finder, $provider);

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
        $config   = m::mock('\Illuminate\Contracts\Config\Repository');
        $event    = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files    = m::mock('\Illuminate\Filesystem\Filesystem');
        $finder   = m::mock('\Orchestra\Extension\Finder');

        $event->shouldReceive('fire')->once()
                ->with('extension.done: laravel/framework', array(array('foo')))
                ->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.done', array('laravel/framework', array('foo')))
                ->andReturnNull();

        $stub = new Dispatcher($config, $event, $files, $finder, $this->getProvider());
        $stub->finish('laravel/framework', array('foo'));
    }
}
