<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Support\ServiceProvider;
use Orchestra\Extension\ProviderRepository;

class ProviderRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Extension\ProviderRepository::provides()
     * method.
     *
     * @test
     */
    public function testServicesMethodWhenEager()
    {
        $service = 'Orchestra\Extension\TestCase\FooServiceProvider';
        $manifestPath = '/var/www/laravel/bootstrap/cache';

        $mock = m::mock($service);
        $app = m::mock('\Orchestra\Contracts\Foundation\Application');
        $events = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $schema = [
            'eager' => true,
            'when' => [],
            'deferred' => [],
        ];

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.json")
            ->shouldReceive('resolveProviderClass')->once()
                ->with($service)->andReturn($mock)
            ->shouldReceive('register')->once()->with($mock)->andReturn($mock);
        $files->shouldReceive('exists')->once()
                ->with("{$manifestPath}/extension.json")->andReturn(false)
            ->shouldReceive('put')->once()
                ->with("{$manifestPath}/extension.json", json_encode([$service => $schema], JSON_PRETTY_PRINT))
                ->andReturnNull();

        $mock->shouldReceive('isDeferred')->once()->andReturn(! $schema['eager']);

        $stub = new ProviderRepository($app, $events, $files);
        $stub->loadManifest();
        $stub->provides([$service]);

        $this->assertTrue($stub->shouldRecompile());
        $this->assertNull($stub->writeManifest());
    }

    /**
     * Test Orchestra\Extension\ProviderRepository::provides()
     * method.
     *
     * @test
     */
    public function testServicesMethodWhenDeferred()
    {
        $service = 'Orchestra\Extension\TestCase\FooServiceProvider';
        $manifestPath = '/var/www/laravel/bootstrap/cache';

        $mock = m::mock($service);
        $app = m::mock('\Orchestra\Contracts\Foundation\Application');
        $events = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $schema = [
            'eager' => false,
            'when' => [],
            'deferred' => [
                'foo' => $service,
            ],
        ];

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.json")
            ->shouldReceive('resolveProviderClass')->once()
                ->with($service)->andReturn($mock)
            ->shouldReceive('addDeferredServices')->once()->andReturn([
                'foo' => $service,
            ]);
        $files->shouldReceive('exists')->once()
                ->with("{$manifestPath}/extension.json")->andReturn(false)
            ->shouldReceive('put')->once()
                ->with("{$manifestPath}/extension.json", json_encode([$service => $schema], JSON_PRETTY_PRINT))
                ->andReturnNull();

        $mock->shouldReceive('isDeferred')->once()->andReturn(! $schema['eager'])
            ->shouldReceive('provides')->once()->andReturn(array_keys($schema['deferred']))
            ->shouldReceive('when')->once()->andReturn($schema['when']);

        $stub = new ProviderRepository($app, $events, $files);
        $stub->loadManifest();
        $stub->provides([$service]);

        $this->assertTrue($stub->shouldRecompile());
        $this->assertNull($stub->writeManifest());
    }

    /**
     * Test Orchestra\Extension\ProviderRepository::provides()
     * method.
     *
     * @test
     */
    public function testServicesMethodWhenManifestExists()
    {
        $service = 'Orchestra\Extension\TestCase\FooServiceProvider';
        $manifestPath = '/var/www/laravel/bootstrap/cache';

        $mock = m::mock($service);
        $app = m::mock('\Orchestra\Contracts\Foundation\Application');
        $events = m::mock('\Illuminate\Contracts\Events\Dispatcher');
        $files = m::mock('\Illuminate\Filesystem\Filesystem');
        $manifestPath = '/var/www/laravel/bootstrap/cache';

        $schema = [
            'eager' => true,
            'when' => [],
            'deferred' => [],
        ];

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.json")
            ->shouldReceive('register')->once()->with($service)->andReturnNull();
        $files->shouldReceive('exists')->once()->with("{$manifestPath}/extension.json")->andReturn(true)
            ->shouldReceive('get')->once()->with("{$manifestPath}/extension.json")
                ->andReturn(json_encode([$service => $schema]));

        $stub = new ProviderRepository($app, $events, $files);
        $stub->loadManifest();
        $stub->provides([$service]);

        $this->assertFalse($stub->shouldRecompile());
        $this->assertNull($stub->writeManifest());
    }
}

class FooServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function when()
    {
        return [];
    }
}
