<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Illuminate\Support\Collection;
use Orchestra\Extension\Finder;

class FinderTest extends \PHPUnit_Framework_TestCase
{
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
        $config['path.app']  = '/foo/app';
        $config['path.base'] = '/foo/path';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);

        $refl  = new \ReflectionObject($stub);
        $paths = $refl->getProperty('paths');
        $paths->setAccessible(true);

        $this->assertEquals(
            ['/foo/app', '/foo/path/vendor/*/*'],
            $paths->getValue($stub)
        );

        $stub->addPath('/foo/public');

        $this->assertEquals(
            ['/foo/app', '/foo/path/vendor/*/*', '/foo/public'],
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
        $config['path.app']  = '/foo/app';
        $config['path.base'] = '/foo/path';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('glob')->once()->with('/foo/app/orchestra.json')
                ->andReturn(['/foo/app/orchestra.json'])
            ->shouldReceive('get')->once()->with('/foo/app/orchestra.json')->andReturn('{"name":"Application"}')
            ->shouldReceive('glob')->once()->with('/foo/path/vendor/*/*/orchestra.json')
                ->andReturn(['/foo/path/vendor/laravel/framework/orchestra.json', '/foo/orchestra.js'])
            ->shouldReceive('get')->once()->with('/foo/path/vendor/laravel/framework/orchestra.json')
                ->andReturn('{"name":"Laravel Framework","path": "vendor::laravel/framework"}');

        $stub = new Finder($files, $config);

        $expected = new Collection([
            'laravel/framework' => [
                'path'        => 'vendor::laravel/framework',
                'source-path' => 'vendor::laravel/framework',
                'name'        => 'Laravel Framework',
                'description' => null,
                'author'      => null,
                'url'         => null,
                'version'     => '>0',
                'config'      => [],
                'autoload'    => [],
                'provides'    => [],
            ],
            'app' => [
                'path'        => 'app::',
                'source-path' => 'app::',
                'name'        => 'Application',
                'description' => null,
                'author'      => null,
                'url'         => null,
                'version'     => '>0',
                'config'      => [],
                'autoload'    => [],
                'provides'    => [],
            ],
        ]);

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
        $config['path.app']  = '/foo/app';
        $config['path.base'] = '/foo/path';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('glob')->once()->with('/foo/app/orchestra.json')
                ->andReturn(['/foo/app/orchestra.json'])
            ->shouldReceive('get')->once()->with('/foo/app/orchestra.json')->andReturn('{"name":"Application"}')
            ->shouldReceive('glob')->once()->with('/foo/path/vendor/*/*/orchestra.json')
                ->andReturn(['/foo/path/vendor/orchestra/foundation/orchestra.json']);

        $stub = new Finder($files, $config);
        $stub->detect();
    }

    /**
     * Test Orchestra\Extension\Finder::detect() method throws
     * exception when unable to parse json manifest file.
     *
     * @expectedException \Orchestra\Contracts\Support\ManifestRuntimeException
     */
    public function testDetectMethodThrowsException()
    {
        $config['path.app']  = '/foo/app';
        $config['path.base'] = '/foo/path';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $files->shouldReceive('glob')->once()->with('/foo/app/orchestra.json')->andReturn([])
            ->shouldReceive('glob')->once()->with('/foo/path/vendor/*/*/orchestra.json')
                ->andReturn(['/foo/path/vendor/laravel/framework/orchestra.json'])
            ->shouldReceive('get')->once()->with('/foo/path/vendor/laravel/framework/orchestra.json')
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
        $config['path.app']  = '/foo/path/app';
        $config['path.base'] = '/foo/path';

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
        $config['path.app']  = '/foo/path/app';
        $config['path.base'] = '/foo/path';

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
        $config['path.app']  = '/foo/path/app';
        $config['path.base'] = '/foo/path';

        $stub = new Finder(m::mock('\Illuminate\Filesystem\Filesystem'), $config);
        $this->assertEquals($expected, $stub->resolveExtensionPath($output));
    }

    /**
     * Test  Orchestra\Extension\Finder::registerExtension() method.
     */
    public function testRegisterExtensionMethod()
    {
        $config['path.app']  = '/foo/app';
        $config['path.base'] = '/foo/path';

        $files = m::mock('\Illuminate\Filesystem\Filesystem');

        $stub = new Finder($files, $config);

        $refl  = new \ReflectionObject($stub);
        $paths = $refl->getProperty('paths');
        $paths->setAccessible(true);

        $this->assertTrue($stub->registerExtension('hello', '/foo/path/modules/'));

        $expected = [
            "/foo/app",
            "/foo/path/vendor/*/*",
            'hello' => '/foo/path/modules',
        ];
        $this->assertEquals($expected, $paths->getValue($stub));
    }

    /**
     * Extension Path provider.
     */
    public function extensionManifestProvider()
    {
        $windowsPath['path.app']  = 'c:\www\laravel\app';
        $windowsPath['path.base'] = 'c:\www\laravel';

        $unixPath['path.app']  = '/var/www/laravel/app';
        $unixPath['path.base'] = '/var/www/laravel';

        return [
            [
                $windowsPath,
                ["laravel", "app"],
                "c:\www\laravel\app/orchestra.json",
            ],
            [
                $windowsPath,
                ["orchestra", "control"],
                "c:\www\laravel\vendor/orchestra/control/orchestra.json",
            ],
            [
                $windowsPath,
                ["orchestra", "story"],
                "c:\www\laravel\vendor/orchestra/story/orchestra.json",
            ],
            [
                $windowsPath,
                ["laravel", "app"],
                "c:\www\laravel\app\orchestra.json",
            ],
            [
                $windowsPath,
                ["orchestra", "control"],
                "c:\www\laravel\vendor\orchestra\control\orchestra.json",
            ],
            [
                $windowsPath,
                ["orchestra", "story"],
                "c:\www\laravel\vendor\orchestra\story\orchestra.json",
            ],
            [
                $unixPath,
                ["laravel", "app"],
                "/var/www/laravel/app/orchestra.json",
            ],
            [
                $unixPath,
                ["orchestra", "control"],
                "/var/www/laravel/vendor/orchestra/control/orchestra.json",
            ],
            [
                $unixPath,
                ["orchestra", "story"],
                "/var/www/laravel/vendor/orchestra/story/orchestra.json",
            ],
        ];
    }

    /**
     * Extension Path provider.
     */
    public function extensionPathProvider()
    {
        return [
            ["foobar", "foobar"],
            ["/foo/path/app/foobar", "app::foobar"],
            ["/foo/path/vendor/foobar", "vendor::foobar"],
            ["/foo/path/foobar", "base::foobar"],
        ];
    }
}
