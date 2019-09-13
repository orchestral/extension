<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Listener\Migrator as Listener;
use Orchestra\Extension\Processors\Migrator as Processor;

class PublishCommand extends ExtensionCommand implements Listener
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:update
        {name : Extension name.}
        {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migration and asset publishing for an extension.';

    /**
     * Execute the console command.
     *
     * @param  \Orchestra\Extension\Processor\Migrator  $migrator
     *
     * @return void
     */
    public function handle(Processor $migrator)
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        return $migrator($this, new Fluent(['name' => $this->argument('name')]));
    }

    /**
     * Response when extension migration has failed.
     *
     * @param  \Illuminate\Support\Fluent  $extension
     * @param  array  $errors
     *
     * @return mixed
     */
    public function migrationHasFailed(Fluent $extension, array $errors)
    {
        $this->error("Extension [{$extension->get('name')}] update has failed.");
    }

    /**
     * Response when extension migration has succeed.
     *
     * @param  \Illuminate\Support\Fluent  $extension
     *
     * @return mixed
     */
    public function migrationHasSucceed(Fluent $extension)
    {
        $this->info("Extension [{$extension->get('name')}] updated.");
    }
}
