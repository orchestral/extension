<?php namespace Orchestra\Extension\Tests;

use Mockery as m;
use Orchestra\Extension\ProviderRepository;

class ProviderRepositoryTest extends \PHPUnit_Framework_TestCase {

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
		$mock = new FooServiceProviderMock;
		$app = m::mock('Application');
		$app->shouldReceive('register')->once()->with($mock)->andReturn(null);

		$this->assertFalse($mock->booted);
		$stub = new ProviderRepository($app);
		$stub->provides(array($mock));
		$this->assertTrue($mock->booted);
	}
}

class FooServiceProviderMock {

	public $booted = false;

	public function boot()
	{
		$this->booted = true;
	}
}
