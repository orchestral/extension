<?php

namespace Orchestra\Extension\Processors;

use Closure;
use Illuminate\Support\Fluent;
use Orchestra\Contracts\Extension\Factory;
use Orchestra\Contracts\Publisher\FilePermissionException;

abstract class Processor
{
    /**
     * The extension factory implementation.
     *
     * @var \Orchestra\Contracts\Extension\Factory
     */
    protected $factory;

    /**
     * Construct a new processor instance.
     *
     * @param \Orchestra\Contracts\Extension\Factory  $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Execute extension processing.
     *
     * @param  object  $listener
     * @param  string  $type
     * @param  \Illuminate\Support\Fluent  $extension
     * @param  \Closure  $callback
     *
     * @return mixed
     */
    protected function execute($listener, $type, Fluent $extension, Closure $callback)
    {
        $name = $extension->get('name');

        try {
            // Check if folder is writable via the web instance, this would
            // avoid issue running Orchestra Platform with debug as true where
            // creating/copying the directory would throw an ErrorException.
            if (! $this->factory->permission($name)) {
                throw new FilePermissionException("[{$name}] is not writable.");
            }

            $callback($this->factory, $name);
        } catch (FilePermissionException $e) {
            return $listener->{"{$type}HasFailed"}($extension, ['error' => $e->getMessage()]);
        }

        return $listener->{"{$type}HasSucceed"}($extension);
    }
}
