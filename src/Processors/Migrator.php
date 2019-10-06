<?php

namespace Orchestra\Extension\Processors;

use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Command\Migrator as Command;
use Orchestra\Contracts\Extension\Factory;
use Orchestra\Contracts\Extension\Listener\Migrator as Listener;

class Migrator extends Processor implements Command
{
    /**
     * Update/migrate an extension.
     *
     * @param  \Orchestra\Contracts\Extension\Listener\Migrator  $listener
     * @param  \Illuminate\Support\Fluent  $extension
     *
     * @return mixed
     */
    public function migrate(Listener $listener, Fluent $extension)
    {
        if (! $this->factory->started($extension->get('name'))) {
            return $listener->abortWhenRequirementMismatched();
        }

        return $this->execute($listener, 'migration', $extension, static function (Factory $factory, $name) {
            $factory->publish($name);
        });
    }

    /**
     * Execute processor using invoke.
     *
     * @param  \Orchestra\Contracts\Extension\Listener\Deactivator  $listener
     * @param  \Illuminate\Support\Fluent  $extension
     *
     * @return mixed
     */
    public function __invoke(Listener $listener, Fluent $extension)
    {
        return $this->migrate($listener, $extension);
    }
}
