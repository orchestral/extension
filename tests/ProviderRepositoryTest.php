<?php namespace Orchestra\Extension\Test;

class ProviderRepositoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		\Mockery::close();
	}

	/**
	 * Test Orchestra\Extension\ProviderRepository::services() 
	 * method.
	 *
	 * @test
	 */
	public function testServicesMethod()
	{
		$app = \Mockery::mock('Application');
		$app->shouldReceive('register')
				->once()->with('Orchestra\Foo\FooServiceProvider')
				->andReturn(null);

		$stub = new \Orchestra\Extension\ProviderRepository($app);
		$stub->provides(array('Orchestra\Foo\FooServiceProvider'));
	}
}