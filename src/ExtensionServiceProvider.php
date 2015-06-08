<?php namespace Orchestra\Extension;

use Orchestra\Extension\Config\Repository;
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
        $this->app->singleton('orchestra.extension', function ($app) {
            $dispatcher = new Dispatcher(
                $app['config'],
                $app['events'],
                $app['files'],
                $app['orchestra.extension.finder'],
                new ProviderRepository($app)
            );

            return new Factory($app, $dispatcher, $app['orchestra.extension.mode']);
        });
    }

    /**
     * Register the service provider for Extension Config Manager.
     *
     * @return void
     */
    protected function registerExtensionConfigManager()
    {
        $this->app->singleton('orchestra.extension.config', function ($app) {
            return new Repository($app['config'], $app['orchestra.memory']);
        });
    }

    /**
     * Register the service provider for Extension Finder.
     *
     * @return void
     */
    protected function registerExtensionFinder()
    {
        $this->app->singleton('orchestra.extension.finder', function ($app) {
            $config = [
                'path.app'  => $app['path'],
                'path.base' => $app['path.base'],
            ];

            return new Finder($app['files'], $config);
        });
    }

    /**
     * Register the service provider for Extension Safe Mode Checker.
     *
     * @return void
     */
    protected function registerExtensionSafeModeChecker()
    {
        $this->app->singleton('orchestra.extension.mode', function ($app) {
            return new SafeModeChecker($app['config'], $app['request']);
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

        $this->addConfigComponent('orchestra/extension', 'orchestra/extension', $path.'/resources/config');
    }

    /**
     * Register extension events.
     *
     * @return void
     */
    protected function registerExtensionEvents()
    {
        $app = $this->app;

        $app->terminating(function () use ($app) {
            $app['orchestra.extension']->finish();
        });
    }
}
