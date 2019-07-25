<?php

namespace Orchestra\Extension;

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
    protected function registerActivateCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.activate', static function () {
            return new Console\ActivateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDeactivateCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.deactivate', static function () {
            return new Console\DeactivateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDetectCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.detect', static function () {
            return new Console\DetectCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerMigrateCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.migrate', static function () {
            return new Console\MigrateCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerPublishCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.publish', static function () {
            return new Console\PublishCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerRefreshCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.refresh', static function () {
            return new Console\RefreshCommand();
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerResetCommand(): void
    {
        $this->app->singleton('orchestra.commands.extension.reset', static function () {
            return new Console\ResetCommand();
        });
    }
}
