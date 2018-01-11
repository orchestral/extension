<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class RefreshCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:refresh
        {name : Extension name.}
        {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh an extension.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $name = $this->argument('name');

        $refresh = $this->laravel['orchestra.extension']->refresh($name);

        if ((bool) $refresh) {
            $this->info("Extension [{$name}] refreshed.");
        } else {
            $this->error("Unable to refresh extension [{$name}].");
        }
    }
}
