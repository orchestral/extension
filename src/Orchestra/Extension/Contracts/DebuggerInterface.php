<?php namespace Orchestra\Extension\Contracts;

interface DebuggerInterface
{
    /**
     * Determine whether current request is in safe mode or not.
     *
     * @return boolean
     */
    public function check();
}
