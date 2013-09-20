<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Orchestra\Extension\Debugger;

class DebuggerTest extends \PHPUnit_Framework_TestCase {
	
	/**
	 * Teardown the test environment.
	 */
	public function tearDown()
	{
		m::close();
	}

	/**
	 * Test Orchestra\Extension\Debugger::check() method when safe mode is 
	 * "on".
	 *
	 * @test
	 */
	public function testCheckMethodWhenSafeModeIsOn()
	{
		$request = m::mock('Request');
		$session = m::mock('Session');
		$app     = array(
			'request' => $request,
			'session' => $session,
		);

		$stub = new Debugger($app);

		$request->shouldReceive('input')->once()->with('safe_mode')->andReturn('on');
		$session->shouldReceive('get')->once()->with('orchestra.safemode', 'off')->andReturn('off')
			->shouldReceive('put')->once()->with('orchestra.safemode', 'on')->andReturn(null);

		$this->assertTrue($stub->check());
	}

	/**
	 * Test Orchestra\Extension\Debugger::check() method when safe mode is 
	 * "off".
	 *
	 * @test
	 */
	public function testCheckMethodWhenSafeModeIsOff()
	{
		$request = m::mock('Request');
		$session = m::mock('Session');
		$app     = array(
			'request' => $request,
			'session' => $session,
		);

		$stub = new Debugger($app);

		$request->shouldReceive('input')->once()->with('safe_mode')->andReturn('off');
		$session->shouldReceive('forget')->once()->with('orchestra.safemode')->andReturn(null);

		$this->assertFalse($stub->check());
	}
}
