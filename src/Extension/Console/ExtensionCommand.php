<?php namespace Orchestra\Extension\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

abstract class ExtensionCommand extends BaseCommand
{
    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('name', InputArgument::REQUIRED, 'Extension Name.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('force', null, InputOption::VALUE_NONE, 'Force the operation to run when in production.'),
        );
    }
}
