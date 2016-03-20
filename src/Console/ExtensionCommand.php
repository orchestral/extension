<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;
use Orchestra\Contracts\Extension\Listener\Extension;

abstract class ExtensionCommand extends Command implements Extension
{
    /**
     * Abort request when extension requirement mismatched.
     *
     * @return mixed
     */
    public function abortWhenRequirementMismatched()
    {
        //
    }

    /**
     * Refresh route cache.
     *
     * @return void
     */
    protected function refreshRouteCache()
    {
        if ($this->laravel->routesAreCached()) {
            $this->call('route:cache');
        }
    }
}
