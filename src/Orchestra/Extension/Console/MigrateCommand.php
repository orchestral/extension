<?php namespace Orchestra\Extension\Console;

class MigrateCommand extends BaseConsole
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migration for orchestra/extension package.';

    /**
     * {@inheritdoc}
     */
    protected function execute()
    {
        $this->call('migrate', array('--package' => 'orchestra/memory'));
    }
}
