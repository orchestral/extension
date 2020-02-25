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
     * @return void
     */
    public function handle(Processor $migrator)
    {
        if (! $this->confirmToProceed()) {
            return 126;
        }

        return $migrator($this, new Fluent(['name' => $this->argument('name')]));
    }

    /**
     * Response when extension migration has failed.
     *
     * @return int
     */
    public function migrationHasFailed(Fluent $extension, array $errors)
    {
        $this->error("Extension [{$extension->get('name')}] update has failed.");

        return 1;
    }

    /**
     * Response when extension migration has succeed.
     *
     * @return int
     */
    public function migrationHasSucceed(Fluent $extension)
    {
        $this->info("Extension [{$extension->get('name')}] updated.");

        return 0;
    }
}
