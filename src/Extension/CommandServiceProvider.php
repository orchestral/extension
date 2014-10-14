<?php namespace Orchestra\Extension;

use Illuminate\Support\ServiceProvider;
use Orchestra\Extension\Console\ResetCommand;
use Orchestra\Extension\Console\MigrateCommand;
use Orchestra\Extension\Console\PublishCommand;
use Orchestra\Extension\Console\RefreshCommand;
use Orchestra\Extension\Console\ActivateCommand;
use Orchestra\Extension\Console\DeactivateCommand;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var boolean
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerActivateCommand();
        $this->registerDeactivateCommand();
        $this->registerDetectCommand();
        $this->registerMigrateCommand();
        $this->registerPublishCommand();
        $this->registerRefreshCommand();
        $this->registerResetCommand();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerActivateCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.activate', function () {
            return new ActivateCommand;
        });

        $this->commands('orchestra.commands.extension.activate');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDeactivateCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.deactivate', function () {
            return new DeactivateCommand;
        });

        $this->commands('orchestra.commands.extension.deactivate');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerDetectCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.detect', function () {
            return new Console\DetectCommand;
        });

        $this->commands('orchestra.commands.extension.detect');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerMigrateCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.migrate', function () {
            return new MigrateCommand;
        });

        $this->commands('orchestra.commands.extension.migrate');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerPublishCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.publish', function () {
            return new PublishCommand;
        });

        $this->commands('orchestra.commands.extension.publish');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerRefreshCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.refresh', function () {
            return new RefreshCommand;
        });

        $this->commands('orchestra.commands.extension.refresh');
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerResetCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.reset', function () {
            return new ResetCommand;
        });

        $this->commands('orchestra.commands.extension.reset');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'orchestra.commands.extension.activate',
            'orchestra.commands.extension.deactivate',
            'orchestra.commands.extension.detect',
            'orchestra.commands.extension.migrate',
            'orchestra.commands.extension.publish',
            'orchestra.commands.extension.refresh',
            'orchestra.commands.extension.reset',
        ];
    }
}
