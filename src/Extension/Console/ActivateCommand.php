<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class ActivateCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an extension.';

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return null;
        }

        $name = $this->argument('name');

        $activated = $this->laravel['orchestra.extension']->activate($name);

        if (!! $activated) {
            $this->info("Extension [{$name}] activated.");
        } else {
            $this->error("Unable to activate extension [{$name}].");
        }
    }
}
