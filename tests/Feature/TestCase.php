<?php

namespace Orchestra\Extension\Tests\Feature;

use Orchestra\Testbench\TestCase as Testing;

abstract class TestCase extends Testing
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            \Orchestra\Extension\ExtensionServiceProvider::class,
            \Orchestra\Memory\MemoryServiceProvider::class,
            \Orchestra\Extension\CommandServiceProvider::class,
        ];
    }
}
