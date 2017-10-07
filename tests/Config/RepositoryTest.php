<?php

namespace Orchestra\Extension\TestCase\Config;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Orchestra\Extension\Config\Repository;

class RepositoryTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Extension\Config\Repository::map() method.
     *
     * @test
     */
    public function testMapMethod()
    {
        $app = new Container();
        $app['encrypter'] = m::mock('\Illuminate\Contracts\Encryption\Encrypter');
        $manager = m::mock('\Orchestra\Memory\MemoryManager', [$app]);
        $memory = m::mock('\Orchestra\Contracts\Memory\Provider');
        $config = m::mock('\Illuminate\Contracts\Config\Repository');

        $manager->shouldReceive('make')->once()->andReturn($memory);
        $memory->shouldReceive('get')->once()
                ->with('extension_laravel/framework', [])
                ->andReturn(['foobar' => 'foobar is awesome'])
            ->shouldReceive('put')->once()
                ->with('extension_laravel/framework', ['foobar' => 'foobar is awesome', 'foo' => 'foobar'])
                ->andReturn(true);
        $config->shouldReceive('set')->once()
                ->with('laravel/framework::foobar', 'foobar is awesome')
                ->andReturn(true)
            ->shouldReceive('get')->once()
                ->with('laravel/framework::foobar')->andReturn('foobar is awesome')
            ->shouldReceive('get')->once()
                ->with('laravel/framework::foo')->andReturn('foobar');

        $stub = new Repository($config, $manager);

        $stub->map('laravel/framework', [
            'foo' => 'laravel/framework::foo',
            'foobar' => 'laravel/framework::foobar',
        ]);
    }
}
