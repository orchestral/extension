<?php namespace Orchestra\Extension\Console;

use Symfony\Component\Console\Input\InputArgument;

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
}
