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
     * Get mocked Orchestra\Extension\ProviderRepository.
     *
     * @return \Orchestra\Extension\ProviderRepository
     */
    protected function getProvider()
    {
        $app = m::mock('\Illuminate\Contracts\Foundation\Application');

        $app->shouldReceive('getCachedServicesPath')->andReturn('/var/www/laravel/bootstrap/cache/service.json');

        return m::mock('\Orchestra\Extension\ProviderRepository', [
            $app,
            m::mock('\Illuminate\Contracts\Events\Dispatcher'),
            m::mock('\Illuminate\Filesystem\Filesystem'),
        ]);
    }

    /**
     * Test Orchestra\Extension\Dispatcher::start() method.
     *
     * @test
     */
    public function testStartMethod()
    {
        $provider = $this->getProvider();
        $app = m::mock('\Illuminate\Contracts\Foundation\Application');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');
        $event = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');
        $finder = m::mock('\Orchestra\Extension\Finder');

        $options1 = [
            'config' => ['handles' => 'laravel'],
            'path' => '/foo/app/laravel/framework/',
            'source-path' => '/foo/app',
            'autoload' => [
                'source-path::hello.php',
                'start.php',
            ],
            'provides' => ['Laravel\FrameworkServiceProvider'],
        ];

        $options2 = [
            'config' => [],
            'path' => '/foo/app/',
        ];

        $config->shouldReceive('set')->once()
                ->with('orchestra/extension::handles.laravel/framework', 'laravel')->andReturnNull();
        $event->shouldReceive('fire')->once()
                ->with('extension.started: laravel/framework', [$options1])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.started', ['laravel/framework', $options1])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted: laravel/framework', [$options1])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted', ['laravel/framework', $options1])->andReturnNull();
        $files->shouldReceive('isFile')->once()->with('/foo/app/hello.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/start.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/src/orchestra.php')->andReturn(true)
            ->shouldReceive('isFile')->once()->with('/foo/app/orchestra.php')->andReturn(false)
            ->shouldReceive('getRequire')->once()->with('/foo/app/hello.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()->with('/foo/app/start.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()->with('/foo/app/src/orchestra.php')->andReturn(true);
        $provider->shouldReceive('provides')->once()
                    ->with(['Laravel\FrameworkServiceProvider'])->andReturn(true)
                ->shouldReceive('writeManifest')->once()->andReturnNull();

        $event->shouldReceive('fire')->once()
                ->with('extension.started: app', [$options2])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.started', ['app', $options2])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted: app', [$options2])->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.booted', ['app', $options2])->andReturnNull();
        $files->shouldReceive('isFile')->once()
                ->with('/foo/app/src/orchestra.php')->andReturn(false)
            ->shouldReceive('isFile')->once()
                ->with('/foo/app/orchestra.php')->andReturn(true)
            ->shouldReceive('getRequire')->once()
                ->with('/foo/app/orchestra.php')->andReturn(true);

        $finder->shouldReceive('resolveExtensionPath')->andReturnUsing(function ($p) {
            return $p;
        });

        $stub = new Dispatcher($app, $config, $event, $files, $finder, $provider);

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
        $app = m::mock('\Illuminate\Contracts\Foundation\Application');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');
        $event = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');
        $finder = m::mock('\Orchestra\Extension\Finder');

        $event->shouldReceive('fire')->once()
                ->with('extension.done: laravel/framework', [['foo']])
                ->andReturnNull()
            ->shouldReceive('fire')->once()
                ->with('extension.done', ['laravel/framework', ['foo']])
                ->andReturnNull();

        $stub = new Dispatcher($app, $config, $event, $files, $finder, $this->getProvider());
        $stub->finish('laravel/framework', ['foo']);
    }
}
