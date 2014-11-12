<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\ConfirmableTrait;

class PublishCommand extends ExtensionCommand
{
    use ConfirmableTrait;

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
    public function fire()
    {
        if (! $this->confirmToProceed()) {
            return null;
        }

        $name = $this->argument('name');

        $this->laravel['orchestra.extension']->publish($name);

        $this->info("Extension [{$name}] updated.");
    }
}
