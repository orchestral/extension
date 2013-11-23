<?php namespace Orchestra\Extension\TestCase;

use Mockery as m;
use Orchestra\Extension\RouteGenerator;

class RouteGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Teardown the test environment.
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Test Orchestra\Extension\RouteGenerator construct proper route.
     *
     * @test
     */
    public function testConstructProperRoute()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->once()->andReturn("http://localhost/laravel")
            ->shouldReceive('secure')->once()->andReturn(false);

        $stub   = new RouteGenerator("foo", $request);

        $refl   = new \ReflectionObject($stub);
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

    /**
     * Test Orchestra\Extension\RouteGenerator with domain route.
     *
     * @test
     */
    public function testRouteWithDomain()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->andReturn(null)
            ->shouldReceive('secure')->andReturn(false);

        $stub1 = new RouteGenerator("//blog.orchestraplatform.com", $request);
        $stub2 = new RouteGenerator("//blog.orchestraplatform.com/hello", $request);
        $stub3 = new RouteGenerator("//blog.orchestraplatform.com/hello/world", $request);

        $this->assertEquals("blog.orchestraplatform.com", $stub1->domain());
        $this->assertEquals("/", $stub1->prefix());
        $this->assertEquals("http://blog.orchestraplatform.com", $stub1->root());
        $this->assertEquals("http://blog.orchestraplatform.com/foo", $stub1->to('foo'));
        $this->assertEquals("http://blog.orchestraplatform.com/foo?bar", $stub1->to('foo?bar'));
        $this->assertEquals("http://blog.orchestraplatform.com/foo?bar=foobar", $stub1->to('foo?bar=foobar'));

        $this->assertEquals("blog.orchestraplatform.com", $stub2->domain());
        $this->assertEquals("hello", $stub2->prefix());
        $this->assertEquals("http://blog.orchestraplatform.com/hello", $stub2->root());
        $this->assertEquals("http://blog.orchestraplatform.com/hello/foo", $stub2->to('foo'));
        $this->assertEquals("http://blog.orchestraplatform.com/hello/foo?bar", $stub2->to('foo?bar'));
        $this->assertEquals("http://blog.orchestraplatform.com/hello/foo?bar=foobar", $stub2->to('foo?bar=foobar'));

        $this->assertEquals("blog.orchestraplatform.com", $stub3->domain());
        $this->assertEquals("hello/world", $stub3->prefix());
        $this->assertEquals("http://blog.orchestraplatform.com/hello/world", $stub3->root());
        $this->assertEquals("http://blog.orchestraplatform.com/hello/world/foo", $stub3->to('foo'));
        $this->assertEquals("http://blog.orchestraplatform.com/hello/world/foo?bar", $stub3->to('foo?bar'));
        $this->assertEquals("http://blog.orchestraplatform.com/hello/world/foo?bar=foobar", $stub3->to('foo?bar=foobar'));
    }

    /**
     * Test Orchestra\Extension\RouteGenerator with domain route when
     * domain name contain wildcard.
     *
     * @test
     */
    public function testRouteWithDomainGivenWildcard()
    {
        $request = m::mock('\Illuminate\Http\Request');

        $request->shouldReceive('root')->andReturn('http://localhost')
            ->shouldReceive('secure')->andReturn(false);

        $stub1 = new RouteGenerator("//blog.{{domain}}", $request);
        $stub2 = new RouteGenerator("//blog.{{domain}}/hello", $request);
        $stub3 = new RouteGenerator("//blog.{{domain}}/hello/world", $request);

        $this->assertEquals("blog.localhost", $stub1->domain());
        $this->assertEquals("/", $stub1->prefix());
        $this->assertEquals("http://blog.localhost", $stub1->root());
        $this->assertEquals("http://blog.localhost/foo", $stub1->to('foo'));
        $this->assertEquals("http://blog.localhost/foo?bar", $stub1->to('foo?bar'));
        $this->assertEquals("http://blog.localhost/foo?bar=foobar", $stub1->to('foo?bar=foobar'));

        $this->assertEquals("blog.localhost", $stub2->domain());
        $this->assertEquals("hello", $stub2->prefix());
        $this->assertEquals("http://blog.localhost/hello", $stub2->root());
        $this->assertEquals("http://blog.localhost/hello/foo", $stub2->to('foo'));
        $this->assertEquals("http://blog.localhost/hello/foo?bar", $stub2->to('foo?bar'));
        $this->assertEquals("http://blog.localhost/hello/foo?bar=foobar", $stub2->to('foo?bar=foobar'));

        $this->assertEquals("blog.localhost", $stub3->domain());
        $this->assertEquals("hello/world", $stub3->prefix());
        $this->assertEquals("http://blog.localhost/hello/world", $stub3->root());
        $this->assertEquals("http://blog.localhost/hello/world/foo", $stub3->to('foo'));
        $this->assertEquals("http://blog.localhost/hello/world/foo?bar", $stub3->to('foo?bar'));
        $this->assertEquals("http://blog.localhost/hello/world/foo?bar=foobar", $stub3->to('foo?bar=foobar'));
    }
}
