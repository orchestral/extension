<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;

class MigrateCommand extends Command
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
    public function handle()
    {
        $path = 'vendor/orchestra/memory/database/migrations';

        $this->call('migrate', ['--path' => $path]);
    }
}
