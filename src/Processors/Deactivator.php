<?php

namespace Orchestra\Extension\Processors;

use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Command\Deactivator as Command;
use Orchestra\Contracts\Extension\Listener\Deactivator as Listener;

class Deactivator extends Processor implements Command
{
    /**
     * Deactivate an extension.
     *
     * @param  \Orchestra\Contracts\Extension\Listener\Deactivator  $listener
     * @param  \Illuminate\Support\Fluent  $extension
     *
     * @return mixed
     */
    public function deactivate(Listener $listener, Fluent $extension)
    {
        if (! $this->factory->started($extension->get('name'))
            && ! $this->factory->activated($extension->get('name'))
        ) {
            return $listener->abortWhenRequirementMismatched();
        }

        $this->factory->deactivate($extension->get('name'));

        return $listener->deactivationHasSucceed($extension);
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
        return $this->deactivate($listener, $extension);
    }
}
