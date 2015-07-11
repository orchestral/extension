<?php namespace Orchestra\Extension;

use Orchestra\Extension\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Support\Providers\ServiceProvider;

class ExtensionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerExtensionFinder();

        $this->registerExtensionConfigManager();

        $this->registerExtensionSafeModeChecker();

        $this->registerExtension();

        $this->registerExtensionEvents();
    }

    /**
     * Register the service provider for Extension.
     *
     * @return void
     */
    protected function registerExtension()
    {
        $this->app->singleton('orchestra.extension', function (Application $app) {
            $dispatcher = new Dispatcher(
                $app->make('config'),
                $app->make('events'),
                $app->make('files'),
                $app->make('orchestra.extension.finder'),
                new ProviderRepository($app)
            );

            return new Factory($app, $dispatcher, $app->make('orchestra.extension.mode'));
        });
    }

    /**
     * Register the service provider for Extension Config Manager.
     *
     * @return void
     */
    protected function registerExtensionConfigManager()
    {
        $this->app->singleton('orchestra.extension.config', function (Application $app) {
            return new Repository(
                $app->make('config'),
                $app->make('orchestra.memory')
            );
        });
    }

    /**
     * Register the service provider for Extension Finder.
     *
     * @return void
     */
    protected function registerExtensionFinder()
    {
        $this->app->singleton('orchestra.extension.finder', function (Application $app) {
            $config = [
                'path.app'  => $app->path(),
                'path.base' => $app->basePath(),
            ];

            return new Finder($app->make('files'), $config);
        });
    }

    /**
     * Register the service provider for Extension Safe Mode Checker.
     *
     * @return void
     */
    protected function registerExtensionSafeModeChecker()
    {
        $this->app->singleton('orchestra.extension.mode', function (Application $app) {
            return new SafeModeChecker(
                $app->make('config'),
                $app->make('request')
            );
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../');

        $this->addConfigComponent('orchestra/extension', 'orchestra/extension', "{$path}/resources/config");
    }

    /**
     * Register extension events.
     *
     * @return void
     */
    protected function registerExtensionEvents()
    {
        $this->app->terminating(function () {
            $this->app->make('orchestra.extension')->finish();
        });
    }
}
