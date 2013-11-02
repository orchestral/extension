<?php namespace Orchestra\Extension\Console;

class ActivateCommand extends ExtensionCommand
{
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
    protected function execute()
    {
        $name = $this->argument('name');

        $this->laravel['orchestra.extension']->activate($name);
        $this->info("Extension [{$name}] activated.");
    }
}
