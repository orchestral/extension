<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class RefreshCommand extends ExtensionCommand
{
    use ConfirmableTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh an extension.';

    /**
     * {@inheritdoc}
     */
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return null;
        }
        
        $name = $this->argument('name');

        $activated = $this->laravel['orchestra.extension']->refresh($name);

        if (!! $activated) {
            $this->info("Extension [{$name}] refreshed.");
        } else {
            $this->error("Unable to refresh extension [{$name}].");
        }
    }
}
