<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Listener\Activator as Listener;
use Orchestra\Extension\Processors\Activator as Processor;

class ActivateCommand extends ExtensionCommand implements Listener
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:activate
        {name : Extension name.}
        {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an extension.';

    /**
     * Execute the console command.
     *
     * @param  \Orchestra\Extension\Processors\Activator  $activator
     *
     * @return void
     */
    public function handle(Processor $activator)
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        return $activator($this, new Fluent(['name' => $this->argument('name')]));
    }

    /**
     * Response when extension activation has failed.
     *
     * @param  \Illuminate\Support\Fluent  $extension
     * @param  array  $errors
     *
     * @return mixed
     */
    public function activationHasFailed(Fluent $extension, array $errors)
    {
        $this->error("Unable to activate extension [{$extension->get('name')}].");
    }

    /**
     * Response when extension activation has succeed.
     *
     * @param  \Illuminate\Support\Fluent  $extension
     *
     * @return mixed
     */
    public function activationHasSucceed(Fluent $extension)
    {
        $this->refreshRouteCache();

        $this->info("Extension [{$extension->get('name')}] activated.");
    }
}
