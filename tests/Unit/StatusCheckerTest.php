<?php

namespace Orchestra\Extension\TestCase\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Orchestra\Extension\StatusChecker;

class StatusCheckerTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
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

        $stub = new StatusChecker($config, $request);

        $request->shouldReceive('input')->once()->with('_mode', 'safe')->andReturn('safe');
        $config->shouldReceive('get')->once()->with('orchestra/extension::mode', 'normal')->andReturn('safe')
            ->shouldReceive('set')->once()->with('orchestra/extension::mode', 'safe')->andReturn(null);

        $this->assertTrue($stub->is('safe'));
        $this->assertFalse($stub->isNot('safe'));
        $this->assertFalse($stub->is('normal'));
        $this->assertEquals('safe', $stub->mode());
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

        $stub = new StatusChecker($config, $request);

        $request->shouldReceive('input')->once()->with('_mode', 'normal')->andReturn(null);
        $config->shouldReceive('get')->once()->with('orchestra/extension::mode', 'normal')->andReturn('normal')
            ->shouldReceive('set')->once()->with('orchestra/extension::mode', 'normal')->andReturn(null);

        $this->assertFalse($stub->is('safe'));
        $this->assertTrue($stub->isNot('safe'));
        $this->assertTrue($stub->is('normal'));
        $this->assertEquals('normal', $stub->mode());
    }
}
