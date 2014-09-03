<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
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
    public function testServicesMethod()
    {
        $mock = m::mock('FooServiceProviderMock');
        $app = m::mock('\Illuminate\Contracts\Foundation\Application');

        $app->shouldReceive('register')->once()->with($mock)->andReturn($mock);

        $stub = new ProviderRepository($app);
        $stub->provides(array($mock));
    }
}
