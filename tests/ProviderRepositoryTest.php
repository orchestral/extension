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
     * Test Orchestra\Extension\ProviderRepository::services()
     * method.
     *
     * @test
     */
    public function testServicesMethodWhenEager()
    {
        $mock = m::mock('\Orchestra\Extension\TestCase\FooServiceProvider');
        $app = m::mock('\Illuminate\Contracts\Foundation\Application');

        $app->shouldReceive('resolveProviderClass')->once()
                ->with('Orchestra\Extension\TestCase\FooServiceProvider')->andReturn($mock)
            ->shouldReceive('register')->once()->with($mock)->andReturn($mock);

        $mock->shouldReceive('isDeferred')->once()->andReturn(false);

        $stub = new ProviderRepository($app);
        $stub->provides([
            'Orchestra\Extension\TestCase\FooServiceProvider',
        ]);
    }

    /**
     * Test Orchestra\Extension\ProviderRepository::services()
     * method.
     *
     * @test
     */
    public function testServicesMethodWhenDeferred()
    {
        $mock = m::mock('\Orchestra\Extension\TestCase\FooServiceProvider');
        $app = m::mock('\Orchestra\Contracts\Kernel\DeferrableServiceContainer', '\Illuminate\Contracts\Foundation\Application');

        $app->shouldReceive('resolveProviderClass')->once()
                ->with('Orchestra\Extension\TestCase\FooServiceProvider')->andReturn($mock)
            ->shouldReceive('getDeferredServices')->once()->andReturn(['events' => '\Illuminate\Events\EventsServiceProvider'])
            ->shouldReceive('setDeferredServices')->once()->andReturn([
                'events' => 'Illuminate\Events\EventsServiceProvider',
                'foo' => 'Orchestra\Extension\TestCase\FooServiceProvider',
            ]);

        $mock->shouldReceive('isDeferred')->once()->andReturn(true)
            ->shouldReceive('provides')->once()->andReturn(['foo']);

        $stub = new ProviderRepository($app);
        $stub->provides([
            'Orchestra\Extension\TestCase\FooServiceProvider',
        ]);
    }
}

class FooServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }
}
