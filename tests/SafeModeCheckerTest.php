<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Orchestra\Extension\SafeModeChecker;

class SafeModeCheckerTest extends \PHPUnit_Framework_TestCase
{
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
        $request = m::mock('\Illuminate\Http\Request');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');

        $stub = new SafeModeChecker($config, $request);

        $request->shouldReceive('input')->once()->with('_mode', 'safe')->andReturn('safe');
        $config->shouldReceive('get')->once()->with('orchestra/extension::mode', 'normal')->andReturn('safe')
            ->shouldReceive('set')->once()->with('orchestra/extension::mode', 'safe')->andReturn(null);

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
        $request = m::mock('\Illuminate\Http\Request');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');

        $stub = new SafeModeChecker($config, $request);

        $request->shouldReceive('input')->once()->with('_mode', 'normal')->andReturn(null);
        $config->shouldReceive('get')->once()->with('orchestra/extension::mode', 'normal')->andReturn('normal')
            ->shouldReceive('set')->once()->with('orchestra/extension::mode', 'normal')->andReturn(null);

        $this->assertFalse($stub->check());
    }
}
