<?php namespace Orchestra\Extension\Console;

use Illuminate\Support\Fluent;
use Illuminate\Console\ConfirmableTrait;
use Orchestra\Extension\Processor\Deactivator as Processor;
use Orchestra\Contracts\Extension\Listener\Deactivator as Listener;

class DeactivateCommand extends ExtensionCommand implements Listener
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
     * Execute the console command.
     *
     * @param  \Orchestra\Extension\Processor\Deactivator  $deactivator
     * @return void
     */
    public function fire(Processor $deactivator)
    {
        if (! $this->confirmToProceed()) {
            return null;
        }

        return $deactivator->deactivate($this, new Fluent(['name' => $this->argument('name')]));
    }

    /**
     * Response when extension deactivation has succeed.
     *
     * @param  \Illuminate\Support\Fluent  $extension
     * @return mixed
     */
    public function deactivationHasSucceed(Fluent $extension)
    {
        $this->info("Extension [{$extension->get('name')}] deactivated.");
    }
}
