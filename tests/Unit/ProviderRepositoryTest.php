<?php

namespace Orchestra\Extension\TestCase\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\ServiceProvider;
use Orchestra\Extension\ProviderRepository;

class ProviderRepositoryTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
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
        $service = FooServiceProvider::class;
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

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.php")
            ->shouldReceive('resolveProvider')->once()
                ->with($service)->andReturn($mock)
            ->shouldReceive('register')->once()->with($mock)->andReturn($mock);
        $files->shouldReceive('exists')->once()
                ->with("{$manifestPath}/extension.php")->andReturn(false)
            ->shouldReceive('put')->once()
                ->with("{$manifestPath}/extension.php", '<?php return '.var_export([$service => $schema], true).';')
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
        $service = FooServiceProvider::class;
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

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.php")
            ->shouldReceive('resolveProvider')->once()
                ->with($service)->andReturn($mock)
            ->shouldReceive('addDeferredServices')->once()->andReturn([
                'foo' => $service,
            ]);
        $files->shouldReceive('exists')->once()
                ->with("{$manifestPath}/extension.php")->andReturn(false)
            ->shouldReceive('put')->once()
                ->with("{$manifestPath}/extension.php", '<?php return '.var_export([$service => $schema], true).';')
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
        $service = FooServiceProvider::class;
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

        $app->shouldReceive('getCachedExtensionServicesPath')->once()->andReturn("{$manifestPath}/extension.php")
            ->shouldReceive('register')->once()->with($service)->andReturnNull();
        $files->shouldReceive('exists')->once()->with("{$manifestPath}/extension.php")->andReturn(true)
            ->shouldReceive('getRequire')->once()->with("{$manifestPath}/extension.php")
                ->andReturn([$service => $schema]);

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
