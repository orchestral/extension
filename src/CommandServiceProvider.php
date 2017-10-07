<?php

namespace Orchestra\Extension;

use Orchestra\Extension\Console\ResetCommand;
use Orchestra\Extension\Console\DetectCommand;
use Orchestra\Extension\Console\MigrateCommand;
use Orchestra\Extension\Console\PublishCommand;
use Orchestra\Extension\Console\RefreshCommand;
use Orchestra\Extension\Console\ActivateCommand;
use Orchestra\Extension\Console\DeactivateCommand;
use Orchestra\Support\Providers\CommandServiceProvider as ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Activate' => 'orchestra.commands.extension.activate',
        'Deactivate' => 'orchestra.commands.extension.deactivate',
        'Detect' => 'orchestra.commands.extension.detect',
        'Migrate' => 'orchestra.commands.extension.migrate',
        'Publish' => 'orchestra.commands.extension.publish',
        'Refresh' => 'orchestra.commands.extension.refresh',
        'Reset' => 'orchestra.commands.extension.reset',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerActivateCommand()
    {
        $this->app->singleton('orchestra.commands.extension.activate', function () {
            return new ActivateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDeactivateCommand()
    {
        $this->app->singleton('orchestra.commands.extension.deactivate', function () {
            return new DeactivateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDetectCommand()
    {
        $this->app->singleton('orchestra.commands.extension.detect', function () {
            return new DetectCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->singleton('orchestra.commands.extension.migrate', function () {
            return new MigrateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerPublishCommand()
    {
        $this->app->singleton('orchestra.commands.extension.publish', function () {
            return new PublishCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerRefreshCommand()
    {
        $this->app->singleton('orchestra.commands.extension.refresh', function () {
            return new RefreshCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerResetCommand()
    {
        $this->app->singleton('orchestra.commands.extension.reset', function () {
            return new ResetCommand();
        });
    }
}
