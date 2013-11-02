<?php namespace Orchestra\Extension\Console;

use Illuminate\Console\Command;

abstract class BaseCommand extends Console
{
    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->execute();
        $this->finish();
    }

     /**
     * Execute the command.
     *
     * @return void
     */
    abstract protected function execute();

    /**
     * Finish the console command.
     *
     * @return void
     */
    protected function finish()
    {
        // Save any changes to orchestra/memory
        $this->laravel['orchestra.memory']->finish();
    }
}
