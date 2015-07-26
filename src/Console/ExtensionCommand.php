<?php namespace Orchestra\Extension\Console;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Orchestra\Contracts\Extension\Listener\Extension;

abstract class ExtensionCommand extends BaseCommand implements Extension
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

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'Extension Name.'],
        ];
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'],
        ];
    }
}
