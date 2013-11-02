<?php namespace Orchestra\Extension\Console;

class PublishCommand extends ExtensionConsole
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migration and asset publishing for an extension.';

    /**
     * {@inheritdoc}
     */
    protected function execute()
    {
        $name = $this->argument('name');

        $this->laravel['orchestra.extension']->publish($name);
        $this->info("Extension [{$name}] updated.");
    }
}
