<?php namespace Orchestra\Extension\Processor;

use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Factory;
use Orchestra\Contracts\Extension\Command\Activator as Command;
use Orchestra\Contracts\Extension\Listener\Activator as Listener;

class Activator extends Processor implements Command
{
    /**
     * Activate an extension.
     *
     * @param  \Orchestra\Contracts\Extension\Listener\Activator $listener
     * @param  \Illuminate\Support\Fluent $extension
     * @return mixed
     */
    public function activate(Listener $listener, Fluent $extension)
    {
        if ($this->factory->started($extension->get('name'))) {
            return $listener->abortWhenRequirementMismatched();
        }

        return $this->execute($listener, 'activation', $extension, $this->getActivationClosure());
    }

    /**
     * Get activation closure.
     *
     * @return callable
     */
    protected function getActivationClosure()
    {
        return function (Factory $factory, $name) {
            $factory->activate($name);
        };
    }
}
