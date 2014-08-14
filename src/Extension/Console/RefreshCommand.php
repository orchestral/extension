<?php namespace Orchestra\Extension\Console;

class RefreshCommand extends ExtensionCommand
{
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
        $name = $this->argument('name');

        $activated = $this->laravel['orchestra.extension']->refresh($name);

        if (!! $activated) {
            $this->info("Extension [{$name}] refreshed.");
        } else {
            $this->error("Unable to refresh extension [{$name}].");
        }
    }
}
