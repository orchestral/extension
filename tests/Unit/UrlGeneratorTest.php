<?php

namespace Orchestra\Extension\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Orchestra\Extension\UrlGenerator;

class UrlGeneratorTest extends TestCase
{
    /**
     * Teardown the test environment.
     */
    protected function tearDown(): void
    {
        m::close();
    }

    /**
     * Test Orchestra\Extension\UrlGenerator construct proper route.
     *
     * @test
     */
    public function testConstructProperRoute()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->once()->andReturn('http://localhost/laravel')
            ->shouldReceive('getScheme')->once()->andReturn('http');

        $stub = (new UrlGenerator($request))->handle('foo');

        $refl = new \ReflectionObject($stub);
        $domain = $refl->getProperty('domain');
        $prefix = $refl->getProperty('prefix');

        $domain->setAccessible(true);
        $prefix->setAccessible(true);

        $this->assertNull($domain->getValue($stub));
        $this->assertEquals('foo', $prefix->getValue($stub));

        $this->assertEquals(null, $stub->domain());
        $this->assertEquals('localhost', $stub->domain(true));
        $this->assertEquals('foo', $stub->prefix());
        $this->assertEquals('laravel/foo', $stub->prefix(true));
        $this->assertEquals('foo', (string) $stub);
        $this->assertEquals('http://localhost/laravel/foo', $stub->root());
    }

    public function isDataProvider()
    {
        return [
            ['foobar', 'foo*', true],
            ['hello', '*ello', true],
            ['helloworld', 'foo*', false],
        ];
    }

    /**
     * Test Orchestra\Extension\UrlGenerator::is() method without domain.
     *
     * @test
     * @dataProvider isDataProvider
     */
    public function testIsMethodWithoutDomain($path, $pattern, $expected)
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('path')->once()->andReturn("acme/$path");

        $stub = (new UrlGenerator($request))->handle('acme');

        $this->assertEquals($expected, $stub->is($pattern));
    }

    /**
     * Test Orchestra\Extension\UrlGenerator::is() method with domain.
     *
     * @test
     * @dataProvider isDataProvider
     */
    public function testIsMethodWithDomain($path, $pattern, $expected)
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('path')->once()->andReturn($path);

        $stub = (new UrlGenerator($request))->handle('//foobar.com');

        $this->assertEquals($expected, $stub->is($pattern));
    }

    /**
     * Test Orchestra\Extension\UrlGenerator::is() method with domain
     * and prefix.
     *
     * @test
     * @dataProvider isDataProvider
     */
    public function testIsMethodWithDomainAndPrefix($path, $pattern, $expected)
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('path')->once()->andReturn("acme/{$path}");

        $stub = (new UrlGenerator($request))->handle('//foobar.com/acme');

        $this->assertEquals($expected, $stub->is($pattern));
    }

    /**
     * Test Orchestra\Extension\UrlGenerator::path method without domain.
     *
     * @test
     */
    public function testPathMethodWithoutDomain()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->once()->andReturn('http://localhost/laravel')
            ->shouldReceive('path')->once()->andReturn('foo')
            ->shouldReceive('path')->once()->andReturn('foo/bar');

        $stub = (new UrlGenerator($request))->handle('foo');

        $this->assertEquals('foo', $stub->path());
        $this->assertEquals('foo/bar', $stub->path());

        $this->assertEquals(['prefix' => 'foo'], $stub->group());
    }

    /**
     * Test Orchestra\Extension\UrlGenerator::path() method with domain.
     *
     * @test
     */
    public function testPathMethodWithDomain()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->once()->andReturn('http://localhost/laravel')
            ->shouldReceive('path')->once()->andReturn('/')
            ->shouldReceive('path')->once()->andReturn('bar');

        $stub = (new UrlGenerator($request))->handle('//foobar.com');

        $this->assertEquals('/', $stub->path());
        $this->assertEquals('bar', $stub->path());

        $this->assertEquals(['prefix' => '/', 'domain' => 'foobar.com'], $stub->group());
    }

    /**
     * Test Orchestra\Extension\UrlGenerator with domain route.
     *
     * @test
     */
    public function testRouteWithDomain()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->andReturn(null)
            ->shouldReceive('getScheme')->andReturn('http');

        $stub1 = (new UrlGenerator($request))->handle('//blog.orchestraplatform.com');
        $stub2 = (new UrlGenerator($request))->handle('//blog.orchestraplatform.com/hello');
        $stub3 = (new UrlGenerator($request))->handle('//blog.orchestraplatform.com/hello/world');

        $this->assertEquals('blog.orchestraplatform.com', $stub1->domain());
        $this->assertEquals('/', $stub1->prefix());
        $this->assertEquals(['prefix' => '/', 'domain' => 'blog.orchestraplatform.com'], $stub1->group());
        $this->assertEquals('http://blog.orchestraplatform.com', $stub1->root());
        $this->assertEquals('http://blog.orchestraplatform.com/foo', $stub1->to('foo'));
        $this->assertEquals('http://blog.orchestraplatform.com/foo?bar', $stub1->to('foo?bar'));
        $this->assertEquals('http://blog.orchestraplatform.com/foo?bar=foobar', $stub1->to('foo?bar=foobar'));

        $this->assertEquals('blog.orchestraplatform.com', $stub2->domain());
        $this->assertEquals('hello', $stub2->prefix());
        $this->assertEquals(['prefix' => 'hello', 'domain' => 'blog.orchestraplatform.com'], $stub2->group());
        $this->assertEquals('http://blog.orchestraplatform.com/hello', $stub2->root());
        $this->assertEquals('http://blog.orchestraplatform.com/hello/foo', $stub2->to('foo'));
        $this->assertEquals('http://blog.orchestraplatform.com/hello/foo?bar', $stub2->to('foo?bar'));
        $this->assertEquals('http://blog.orchestraplatform.com/hello/foo?bar=foobar', $stub2->to('foo?bar=foobar'));

        $this->assertEquals('blog.orchestraplatform.com', $stub3->domain());
        $this->assertEquals('hello/world', $stub3->prefix());
        $this->assertEquals(['prefix' => 'hello/world', 'domain' => 'blog.orchestraplatform.com'], $stub3->group());
        $this->assertEquals('http://blog.orchestraplatform.com/hello/world', $stub3->root());
        $this->assertEquals('http://blog.orchestraplatform.com/hello/world/foo', $stub3->to('foo'));
        $this->assertEquals('http://blog.orchestraplatform.com/hello/world/foo?bar', $stub3->to('foo?bar'));
        $this->assertEquals('http://blog.orchestraplatform.com/hello/world/foo?bar=foobar', $stub3->to('foo?bar=foobar'));
    }

    /**
     * Test Orchestra\Extension\UrlGenerator with domain route when
     * domain name contain wildcard.
     *
     * @test
     */
    public function testRouteWithDomainGivenWildcard()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->andReturn('http://localhost')
            ->shouldReceive('getScheme')->andReturn('http');

        $stub1 = (new UrlGenerator($request))->handle('//blog.{{domain}}');
        $stub2 = (new UrlGenerator($request))->handle('//blog.{{domain}}/hello');
        $stub3 = (new UrlGenerator($request))->handle('//blog.{{domain}}/hello/world');

        $this->assertEquals('blog.localhost', $stub1->domain());
        $this->assertEquals('/', $stub1->prefix());
        $this->assertEquals(['prefix' => '/', 'domain' => 'blog.localhost'], $stub1->group());
        $this->assertEquals('http://blog.localhost', $stub1->root());
        $this->assertEquals('http://blog.localhost/foo', $stub1->to('foo'));
        $this->assertEquals('http://blog.localhost/foo?bar', $stub1->to('foo?bar'));
        $this->assertEquals('http://blog.localhost/foo?bar=foobar', $stub1->to('foo?bar=foobar'));

        $this->assertEquals('blog.localhost', $stub2->domain());
        $this->assertEquals('hello', $stub2->prefix());
        $this->assertEquals(['prefix' => 'hello', 'domain' => 'blog.localhost'], $stub2->group());
        $this->assertEquals('http://blog.localhost/hello', $stub2->root());
        $this->assertEquals('http://blog.localhost/hello/foo', $stub2->to('foo'));
        $this->assertEquals('http://blog.localhost/hello/foo?bar', $stub2->to('foo?bar'));
        $this->assertEquals('http://blog.localhost/hello/foo?bar=foobar', $stub2->to('foo?bar=foobar'));

        $this->assertEquals('blog.localhost', $stub3->domain());
        $this->assertEquals('hello/world', $stub3->prefix());
        $this->assertEquals(['prefix' => 'hello/world', 'domain' => 'blog.localhost'], $stub3->group());
        $this->assertEquals('http://blog.localhost/hello/world', $stub3->root());
        $this->assertEquals('http://blog.localhost/hello/world/foo', $stub3->to('foo'));
        $this->assertEquals('http://blog.localhost/hello/world/foo?bar', $stub3->to('foo?bar'));
        $this->assertEquals('http://blog.localhost/hello/world/foo?bar=foobar', $stub3->to('foo?bar=foobar'));
    }
}
