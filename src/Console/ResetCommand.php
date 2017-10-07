<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class ResetCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:reset
        {name : Extension name.}
        {--force : Force the operation to run when in production.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset an extension.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        if (! $this->confirmToProceed()) {
            return;
        }

        $name = $this->argument('name');

        $this->laravel['orchestra.extension.finder']->detect();

        $reset = $this->laravel['orchestra.extension']->reset($name);

        if ((bool) $reset) {
            $this->info("Extension [{$name}] has been reset.");
        } else {
            $this->error("Unable to reset extension [{$name}].");
        }
    }
}
