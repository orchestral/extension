<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;
use Orchestra\Support\Str;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ExtensionCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'orchestra:extension';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Orchestra\Extension Command';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->routeToCommand();
        $this->finish();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    protected function routeToCommand()
    {
        $action = $this->argument('action');
        $method = null;

        $migrate = array('install', 'upgrade');
        $case    = array('detect', 'activate', 'deactivate', 'update');

        if (in_array($action, $migrate)) {
            $method = 'Migration';
        } elseif (in_array($action, $case)) {
            $method = Str::title($action);
        } else {
            // If none of the action is triggered, we should notify the
            // error to user.
            return $this->error("Invalid action [{$action}].");
        }

        return call_user_func(array($this, "fire{$method}"));
    }

    /**
     * Finish the console command.
     *
     * @return void
     */
    protected function finish()
    {
        // Save any changes to orchestra/memory
        $this->laravel['orchestra.memory']->finish();
    }

    /**
     * Fire migration process.
     *
     * @return void
     */
    protected function fireMigration()
    {
        $this->call('migrate', array('--package' => 'orchestra/memory'));
        $this->info('orchestra/extension has been migrated');
    }

    /**
     * Fire extension detection.
     *
     * @return void
     */
    protected function fireDetect()
    {
        $service    = $this->laravel['orchestra.extension'];
        $extensions = $service->detect();

        if (empty($extensions)) {
            return $this->line("<comment>No extension detected!</comment>");
        }

        $this->line("<info>Detected:</info>");

        foreach ($extensions as $name => $options) {
            $output = ($service->started($name) ? "✓ <info>%s [%s]</info>" : "- <comment>%s [%s]</comment>");

            $this->line(sprintf($output, $name, $options['version']));
        }
    }

    /**
     * Update an extension.
     *
     * @return void
     */
    protected function fireUpdate()
    {
        $name = $this->argument('name');
        $this->laravel['orchestra.extension']->publish($name);
        $this->info("Extension [{$name}] updated.");
    }

    /**
     * Fire extension activation.
     *
     * @return void
     */
    protected function fireActivate()
    {
        $name = $this->argument('name');
        $this->laravel['orchestra.extension']->activate($name);
        $this->info("Extension [{$name}] activated.");
    }

    /**
     * Fire extension activation.
     *
     * @return void
     */
    protected function fireDeactivate()
    {
        $name = $this->argument('name');
        $this->laravel['orchestra.extension']->deactivate($name);
        $this->info("Extension [{$name}] deactivated.");
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('action', InputArgument::OPTIONAL, "Type of action, e.g: 'install', 'upgrade', 'detect', 'activate', 'deactivate'."),
            array('name', InputArgument::OPTIONAL, 'Extension Name.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array();
    }
}
