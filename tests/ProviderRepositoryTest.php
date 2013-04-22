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
		$provider = \Mockery::mock('\Illuminate\Foundation\ProviderRepository');

		$app = array(
			'orchestra.service.provider' => $provider
		);
		
		$provider->shouldReceive('load')
			->once()->with($app, array('Orchestra\Foo\FooServiceProvider'))
				->andReturn(null);

		$stub = new \Orchestra\Extension\ProviderRepository($app);
		$stub->services(array('Orchestra\Foo\FooServiceProvider'));
	}
}