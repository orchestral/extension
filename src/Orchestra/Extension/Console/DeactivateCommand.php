<?php namespace Orchestra\Extension\Console;

class DeactivateCommand extends ExtensionConsole
{
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
    protected function execute()
    {
        $name = $this->argument('name');

        $this->laravel['orchestra.extension']->deactivate($name);
        $this->info("Extension [{$name}] deactivated.");
    }
}
