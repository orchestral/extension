<?php

namespace Orchestra\Extension\Bootstrap;

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
        $app->make('orchestra.extension')
                ->attach($app->make('orchestra.memory')->makeOrFallback())
                ->boot();
    }
}
