<?php namespace Orchestra\Extension\Tests;

use Mockery as m;
use Orchestra\Extension\Finder;

class FinderTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test constructing a new Orchestra\Extension\Finder.
	 *
	 * @test
	 */
	public function testConstructMethod()
	{
		$app = array(
			'path'      => '/foo/app',
			'path.base' => '/foo/path'
		);

		$stub = new Finder($app);

		$refl  = new \ReflectionObject($stub);
		$paths = $refl->getProperty('paths');
		$paths->setAccessible(true);

		$this->assertEquals(array('/foo/app/', '/foo/path/vendor/*/*/', '/foo/path/workbench/*/*/'), 
			$paths->getValue($stub)); 

		$stub->addPath('/foo/public');

		$this->assertEquals(array('/foo/app/', '/foo/path/vendor/*/*/', '/foo/path/workbench/*/*/', '/foo/public'), 
			$paths->getValue($stub)); 
	}

	/**
	 * Test Orchestra\Extension\Finder::detect() method.
	 *
	 * @test
	 */
	public function testDetectMethod()
	{
		$files = m::mock('\Illuminate\Filesystem\Filesystem');
		$app = array(
			'path'      => '/foo/app/',
			'path.base' => '/foo/path/',
			'files'     => $files,
		);

		$files->shouldReceive('glob')->once()
				->with('/foo/app/orchestra.json')->andReturn(array('/foo/app/orchestra.json'))
			->shouldReceive('get')->once()
				->with('/foo/app/orchestra.json')->andReturn('{"name":"Application"}')
			->shouldReceive('glob')->once()
				->with('/foo/path/vendor/*/*/orchestra.json')
				->andReturn(array('/foo/path/vendor/laravel/framework/orchestra.json', '/foo/orchestra.json'))
			->shouldReceive('get')->once()
				->with('/foo/path/vendor/laravel/framework/orchestra.json')
				->andReturn('{"name":"Laravel Framework"}')
			->shouldReceive('glob')->once()
				->with('/foo/path/workbench/*/*/orchestra.json')->andReturn(array());

		$stub     = new Finder($app);
		$expected = array(
			'laravel/framework' => array(
				'path'        => '/foo/path/vendor/laravel/framework/',
				'name'        => 'Laravel Framework',
				'description' => null,
				'author'      => null,
				'url'         => null,
				'version'     => '>0',
				'config'      => array(),
				'provide'     => array(),
			),
			'app' => array(
				'path'        => '/foo/app/',
				'name'        => 'Application',
				'description' => null,
				'author'      => null,
				'url'         => null,
				'version'     => '>0',
				'config'      => array(),
				'provide'     => array(),
			),
		);

		$this->assertEquals($expected, $stub->detect());
	}

	/**
	 * Test Orchestra\Extension\Finder::detect() method giveb reserved name 
	 * throws exception.
	 *
	 * @expectedException \RuntimeException
	 */
	public function testDetectMethodGivenReservedNameThrowsException()
	{
		$files = m::mock('\Illuminate\Filesystem\Filesystem');
		$app = array(
			'path'      => '/foo/app/',
			'path.base' => '/foo/path/',
			'files'     => $files,
		);

		$files->shouldReceive('glob')->once()
				->with('/foo/app/orchestra.json')->andReturn(array('/foo/app/orchestra.json'))
			->shouldReceive('get')->once()
				->with('/foo/app/orchestra.json')->andReturn('{"name":"Application"}')
			->shouldReceive('glob')->once()
				->with('/foo/path/vendor/*/*/orchestra.json')
				->andReturn(array('/foo/path/vendor/orchestra/foundation/orchestra.json'));
		
		$stub = new Finder($app);
		$stub->detect();
	}

	/**
	 * Test Orchestra\Extension\Finder::detect() method throws 
	 * exception when unable to parse json manifest file.
	 *
	 * @expectedException \Orchestra\Extension\ManifestRuntimeException
	 */
	public function testDetectMethodThrowsException()
	{
		$files = m::mock('\Illuminate\Filesystem\Filesystem');
		$app   = array(
			'path'      => '/foo/app',
			'path.base' => '/foo/path',
			'files'     => $files,
		);

		$files->shouldReceive('glob')
				->with('/foo/app/orchestra.json')->once()
				->andReturn(array())
			->shouldReceive('glob')
				->with('/foo/path/vendor/*/*/orchestra.json')->once()
				->andReturn(array('/foo/path/vendor/laravel/framework/orchestra.json'))
			->shouldReceive('glob')
				->with('/foo/path/workbench/*/*/orchestra.json')->never()
				->andReturn(array())
			->shouldReceive('get')
				->with('/foo/path/vendor/laravel/framework/orchestra.json')->once()
				->andReturn('{"name":"Laravel Framework}');

		with(new Finder($app))->detect();
	}
}
