<?php namespace Orchestra\Extension;

use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    protected function registerActivateCommand()
    {
        $this->app->bindShared('orchestra.commands.extension.activate', function () {
            return new Console\ActivateCommand;
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
            return new Console\DeactivateCommand;
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
            return new Console\MigrateCommand;
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
            return new Console\PublishCommand;
        });

        $this->commands('orchestra.commands.extension.publish');
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array(
            'orchestra.commands.extension.activate',
            'orchestra.commands.extension.deactivate',
            'orchestra.commands.extension.detect',
            'orchestra.commands.extension.migrate',
            'orchestra.commands.extension.publish',
        );
    }
}
