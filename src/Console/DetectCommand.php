<?php

namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;

class DetectCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'extension:detect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect available extensions in the application.';

    /**
     * {@inheritdoc}
     */
    public function handle()
    {
        $provider = $this->laravel->make('orchestra.extension');
        $extensions = $provider->detect();

        if ($this->option('quiet')) {
            return;
        }

        if ($extensions->isEmpty()) {
            return $this->line('<comment>No extension detected!</comment>');
        }

        $header = ['Extension', 'Version', 'Activate'];

        $this->table($header, $extensions->map(static function ($options, $name) use ($provider) {
            return [
                $name,
                $options['version'],
                $provider->started($name) ? '    ✓' : '',
            ];
        })->all());
    }
}
