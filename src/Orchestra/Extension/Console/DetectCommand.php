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
    protected function execute()
    {
        $service = $this->laravel['orchestra.extension'];
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
}
