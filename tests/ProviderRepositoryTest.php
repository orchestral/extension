<?php namespace Orchestra\Extension\Test;

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
		$app = m::mock('Application');
		$app->shouldReceive('register')->once()->with('Orchestra\Foo\FooServiceProvider')->andReturn(null);

		$stub = new ProviderRepository($app);
		$stub->provides(array('Orchestra\Foo\FooServiceProvider'));
	}
}
