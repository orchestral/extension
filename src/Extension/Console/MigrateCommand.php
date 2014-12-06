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
        $path = $this->laravel['path.base'].'/vendor/orchestra/memory/src/migrations';

        $this->call('migrate', ['--path' => $path]);
    }
}
