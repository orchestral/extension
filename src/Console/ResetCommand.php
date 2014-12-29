<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class ResetCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:reset';

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
            return null;
        }

        $name = $this->argument('name');

        $this->laravel['orchestra.extension.finder']->detect();

        $reset = $this->laravel['orchestra.extension']->reset($name);

        if (!! $reset) {
            $this->info("Extension [{$name}] has been reset.");
        } else {
            $this->error("Unable to reset extension [{$name}].");
        }
    }
}
