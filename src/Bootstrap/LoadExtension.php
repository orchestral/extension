<?php namespace Orchestra\Extension\Bootstrap;

use Illuminate\Contracts\Foundation\Application;

class LoadExtension
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $extension = $app['orchestra.extension'];

        $extension->attach($app['orchestra.memory']->makeOrFallback());
        $extension->boot();
    }
}
