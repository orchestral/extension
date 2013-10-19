<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Container\Container;
use Orchestra\Extension\Debugger;

class DebuggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Setup the test environment.
     */
    public function setUp()
    {
        $this->app = new Container;
    }

    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        unset($this->app);
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
        $app     = $this->app;
        $request = m::mock('Request');
        $session = m::mock('Session');

        $app['request'] = $request;
        $app['session'] = $session;

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
        $app     = $this->app;
        $request = m::mock('Request');
        $session = m::mock('Session');

        $app['request'] = $request;
        $app['session'] = $session;

        $stub = new Debugger($app);

        $request->shouldReceive('input')->once()->with('safe_mode')->andReturn('off');
        $session->shouldReceive('forget')->once()->with('orchestra.safemode')->andReturn(null);

        $this->assertFalse($stub->check());
    }
}
