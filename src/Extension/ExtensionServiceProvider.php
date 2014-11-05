<?php namespace Orchestra\Extension;

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
            $safe       = new SafeModeChecker($app['request'], $app['session.store']);
            $provider   = new ProviderRepository($app);
            $dispatcher = new Dispatcher(
                $app['config'],
                $app['events'],
                $app['files'],
                $app['orchestra.extension.finder'],
                $provider
            );

            return new Factory($app, $dispatcher, $safe);
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
            return new ConfigManager($app['config'], $app['orchestra.memory']);
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
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../');

        $this->addConfigComponent('orchestra/extension', 'orchestra/extension', $path.'/config');
    }

    /**
     * Register extension events.
     *
     * @return void
     */
    protected function registerExtensionEvents()
    {
        $app = $this->app;

        $app['router']->after(function () use ($app) {
            $app['orchestra.extension']->finish();
        });
    }
}
