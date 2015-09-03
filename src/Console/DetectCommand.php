<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;

class DetectCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'extension:detect
        {--quiet : Do not output any message. }';

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
        $service    = $this->laravel['orchestra.extension'];
        $extensions = $service->detect();

        if ($this->option('quiet')) {
            return ;
        }

        if (empty($extensions)) {
            return $this->line('<comment>No extension detected!</comment>');
        }

        $header  = ['Extension', 'Version', 'Activate'];
        $content = [];

        foreach ($extensions as $name => $options) {
            $content[] = [
                $name,
                $options['version'],
                $service->started($name) ? '    ✓' : '',
            ];
        }

        $this->table($header, $content);
    }
}
