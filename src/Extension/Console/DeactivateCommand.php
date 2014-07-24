<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class DeactivateCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:deactivate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate an extension.';

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return null;
        }

        $name = $this->argument('name');

        $deactivated = $this->laravel['orchestra.extension']->deactivate($name);

        if (!! $deactivated) {
            $this->info("Extension [{$name}] deactivated.");
        } else {
            $this->error("Unable to deactivate extension [{$name}].");
        }
    }
}
