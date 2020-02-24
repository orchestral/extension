<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Listener\Deactivator as Listener;
use Orchestra\Extension\Processors\Deactivator as Processor;

class DeactivateCommand extends ExtensionCommand implements Listener
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:deactivate
        {name : Extension name.}
        {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate an extension.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Processor $deactivator)
    {
        if (! $this->confirmToProceed()) {
            return 126;
        }

        return $deactivator($this, new Fluent(['name' => $this->argument('name')]));
    }

    /**
     * Response when extension deactivation has succeed.
     *
     * @return int
     */
    public function deactivationHasSucceed(Fluent $extension)
    {
        $this->refreshRouteCache();

        $this->info("Extension [{$extension->get('name')}] deactivated.");

        return 0;
    }
}
