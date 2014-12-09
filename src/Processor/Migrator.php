<?php namespace Orchestra\Extension\Processor;

use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Factory;
use Orchestra\Contracts\Extension\Command\Migrator as Command;
use Orchestra\Contracts\Extension\Listener\Migrator as Listener;

class Migrator extends Processor implements Command
{
    /**
     * Update/migrate an extension.
     *
     * @param  \Orchestra\Contracts\Extension\Listener\Migrator  $listener
     * @param  \Illuminate\Support\Fluent  $extension
     * @return mixed
     */
    public function migrate(Listener $listener, Fluent $extension)
    {
        if (! $this->factory->started($extension->get('name'))) {
            return $listener->abortWhenRequirementMismatched();
        }

        return $this->execute($listener, 'migration', $extension, $this->getMigrationClosure());
    }

    /**
     * Get migration closure.
     *
     * @return callable
     */
    protected function getMigrationClosure()
    {
        return function (Factory $factory, $name) {
            $factory->publish($name);
        };
    }
}
