<?php namespace Orchestra\Extension\Console;

class DetectCommand extends BaseCommand
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
    public function fire()
    {
        $service = $this->laravel['orchestra.extension'];
        $extensions = $service->detect();

        if (empty($extensions)) {
            return $this->line("<comment>No extension detected!</comment>");
        }

        $header = ['Extension', 'Version', 'Activate'];
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
