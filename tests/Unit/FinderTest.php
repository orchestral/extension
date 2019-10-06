<?php

namespace Orchestra\Extension\Tests\Unit;

use Illuminate\Support\Collection;
use Mockery as m;
use Orchestra\Extension\Finder;
use PHPUnit\Framework\TestCase;

class FinderTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
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
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';
        $config['path.composer'] = '/var/www/laravel/composer.lock';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);

        $refl = new \ReflectionObject($stub);
        $paths = $refl->getProperty('paths');
        $paths->setAccessible(true);

        $this->assertEquals(
            ['/var/www/laravel/app', '/var/www/laravel/vendor/*/*'],
            $paths->getValue($stub)
        );

        $stub->addPath('/var/www/laravel/public');

        $this->assertEquals(
            ['/var/www/laravel/app', '/var/www/laravel/vendor/*/*', '/var/www/laravel/public'],
            $paths->getValue($stub)
        );
    }

    /**
     * Test Orchestra\Extension\Finder::detect() method.
     *
     * @test
     */
    public function testDetectMethod()
    {
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';
        $config['path.composer'] = '/var/www/laravel/composer.lock';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('glob')->once()->with('/var/www/laravel/app/orchestra.json')
                ->andReturn(['/var/www/laravel/app/orchestra.json'])
            ->shouldReceive('get')->once()->with('/var/www/laravel/app/orchestra.json')->andReturn('{"name":"Application"}')
            ->shouldReceive('glob')->once()->with('/var/www/laravel/vendor/*/*/orchestra.json')
                ->andReturn(['/var/www/laravel/vendor/laravel/framework/orchestra.json', '/var/www/laravel/orchestra.js'])
            ->shouldReceive('get')->once()->with('/var/www/laravel/vendor/laravel/framework/orchestra.json')
                ->andReturn('{"name": "laravel/framework", "description": "Laravel Framework", "path": "vendor::laravel/framework"}')
            ->shouldReceive('exists')->once()->with('/var/www/laravel/composer.lock')->andReturn(true)
            ->shouldReceive('get')->once()->with('/var/www/laravel/composer.lock')
                ->andReturn('{"packages":[{"name": "laravel/framework", "description": "Laravel Framework", "version": "v5.1.10"}]}');

        $stub = new Finder($files, $config);

        $expected = new Collection([
            'laravel/framework' => [
                'path' => 'vendor::laravel/framework',
                'source-path' => 'vendor::laravel/framework',
                'name' => 'laravel/framework',
                'description' => 'Laravel Framework',
                'author' => null,
                'url' => null,
                'version' => 'v5.1.10',
                'config' => [],
                'autoload' => [],
                'provides' => [],
                'plugin' => null,
            ],
            'app' => [
                'path' => 'app::',
                'source-path' => 'app::',
                'name' => 'Application',
                'description' => null,
                'author' => null,
                'url' => null,
                'version' => '*',
                'config' => [],
                'autoload' => [],
                'provides' => [],
                'plugin' => null,
            ],
        ]);

        $this->assertEquals($expected, $stub->detect());
    }

    /**
     * Test Orchestra\Extension\Finder::detect() method giveb reserved name
     * throws exception.
     *
     * @test
     */
    public function testDetectMethodGivenReservedNameThrowsException()
    {
        $this->expectException('RuntimeException');

        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';
        $config['path.composer'] = '/var/www/laravel/composer.lock';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('glob')->once()->with('/var/www/laravel/app/orchestra.json')
                ->andReturn(['/var/www/laravel/app/orchestra.json'])
            ->shouldReceive('get')->once()->with('/var/www/laravel/app/orchestra.json')->andReturn('{"name":"Application"}')
            ->shouldReceive('glob')->once()->with('/var/www/laravel/vendor/*/*/orchestra.json')
                ->andReturn(['/var/www/laravel/vendor/orchestra/foundation/orchestra.json'])
            ->shouldReceive('exists')->once()->with('/var/www/laravel/composer.lock')->andReturn(true)
            ->shouldReceive('get')->once()->with('/var/www/laravel/composer.lock')
                ->andReturn('{"packages":[{"name": "laravel/framework", "description": "Laravel Framework", "version": "v5.1.10"}]}');

        $stub = new Finder($files, $config);
        $stub->detect();
    }

    /**
     * Test Orchestra\Extension\Finder::detect() method throws
     * exception when unable to parse json manifest file.
     *
     * @test
     */
    public function testDetectMethodThrowsException()
    {
        $this->expectException('Orchestra\Contracts\Support\ManifestRuntimeException');

        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';
        $config['path.composer'] = '/var/www/laravel/composer.lock';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('exists')->once()->with('/var/www/laravel/composer.lock')->andReturn(true)
            ->shouldReceive('get')->once()->with('/var/www/laravel/composer.lock')
                ->andReturn('{"packages":[{"name": "laravel/framework", "description": "Laravel Framework", "version": "v5.1.10"}]}')
                ->shouldReceive('glob')->once()->with('/var/www/laravel/app/orchestra.json')->andReturn([])
            ->shouldReceive('glob')->once()->with('/var/www/laravel/vendor/*/*/orchestra.json')
                ->andReturn(['/var/www/laravel/vendor/laravel/framework/orchestra.json'])
            ->shouldReceive('get')->once()->with('/var/www/laravel/vendor/laravel/framework/orchestra.json')
                ->andReturn('{"name":"Laravel Framework}');

        with(new Finder($files, $config))->detect();
    }

    /**
     * Test Orchestra\Extension\Finder::guessExtensionPath() method.
     *
     * @test
     * @dataProvider extensionPathProvider
     */
    public function testGuessExtensionPathMethod($output, $expected)
    {
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);
        $this->assertEquals($expected, $stub->guessExtensionPath($output));
    }

    /**
     * Test Orchestra\Extension\Finder::resolveExtensionNamespace() method
     * with mixed directory separator.
     *
     * @test
     * @dataProvider extensionManifestProvider
     */
    public function testResolveExtensionNamespace($path, $expected, $output)
    {
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);
        $this->assertEquals($expected, $stub->resolveExtensionNamespace($output));
    }

    /**
     * Test Orchestra\Extension\Finder::resolveExtensionPath() method.
     *
     * @test
     * @dataProvider extensionPathProvider
     */
    public function testResolveExtensionPathMethod($expected, $output)
    {
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);
        $this->assertEquals($expected, $stub->resolveExtensionPath($output));
    }

    /**
     * Test  Orchestra\Extension\Finder::registerExtension() method.
     */
    public function testRegisterExtensionMethod()
    {
        $config['path.app'] = '/var/www/laravel/app';
        $config['path.base'] = '/var/www/laravel';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $stub = new Finder($files, $config);

        $refl = new \ReflectionObject($stub);
        $paths = $refl->getProperty('paths');
        $paths->setAccessible(true);

        $this->assertTrue($stub->registerExtension('hello', '/var/www/laravel/modules/'));

        $expected = [
            '/var/www/laravel/app',
            '/var/www/laravel/vendor/*/*',
            'hello' => '/var/www/laravel/modules',
        ];

        $this->assertEquals($expected, $paths->getValue($stub));
    }

    /**
     * Extension Path provider.
     */
    public function extensionManifestProvider()
    {
        $windowsPath['path.app'] = 'c:\www\laravel\app';
        $windowsPath['path.base'] = 'c:\www\laravel';

        $unixPath['path.app'] = '/var/www/laravel/app';
        $unixPath['path.base'] = '/var/www/laravel';

        return [
            [
                $windowsPath,
                ['laravel', 'app'],
                "c:\www\laravel\app/orchestra.json",
            ],
            [
                $windowsPath,
                ['orchestra', 'control'],
                "c:\www\laravel\vendor/orchestra/control/orchestra.json",
            ],
            [
                $windowsPath,
                ['orchestra', 'story'],
                "c:\www\laravel\vendor/orchestra/story/orchestra.json",
            ],
            [
                $windowsPath,
                ['laravel', 'app'],
                "c:\www\laravel\app\orchestra.json",
            ],
            [
                $windowsPath,
                ['orchestra', 'control'],
                "c:\www\laravel\vendor\orchestra\control\orchestra.json",
            ],
            [
                $windowsPath,
                ['orchestra', 'story'],
                "c:\www\laravel\vendor\orchestra\story\orchestra.json",
            ],
            [
                $unixPath,
                ['laravel', 'app'],
                '/var/www/laravel/app/orchestra.json',
            ],
            [
                $unixPath,
                ['orchestra', 'control'],
                '/var/www/laravel/vendor/orchestra/control/orchestra.json',
            ],
            [
                $unixPath,
                ['orchestra', 'story'],
                '/var/www/laravel/vendor/orchestra/story/orchestra.json',
            ],
        ];
    }

    /**
     * Extension Path provider.
     */
    public function extensionPathProvider()
    {
        return [
            ['foobar', 'foobar'],
            ['/var/www/laravel/app/foobar', 'app::foobar'],
            ['/var/www/laravel/vendor/foobar', 'vendor::foobar'],
            ['/var/www/laravel/foobar', 'base::foobar'],
        ];
    }
}
