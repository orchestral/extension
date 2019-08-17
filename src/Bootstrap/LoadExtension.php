<?php

namespace Orchestra\Extension\Bootstrap;

use Illuminate\Contracts\Container\Container;

class LoadExtension
{
    /**
     * Bootstrap the given application.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     *
     * @return void
     */
    public function bootstrap(Container $app)
    {
        $app->make('orchestra.extension')
                ->attach($app->make('orchestra.memory')->makeOrFallback())
                ->boot();
    }
}
