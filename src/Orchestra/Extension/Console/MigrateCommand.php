<?php namespace Orchestra\Extension\Console;

class MigrateCommand extends BaseCommand
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
    public function fire()
    {
        $this->call('migrate', array('--package' => 'orchestra/memory'));
    }
}
