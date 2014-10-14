<?php namespace Orchestra\Extension;

use Illuminate\Support\ServiceProvider;

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
        $this->app->bindShared('orchestra.extension', function ($app) {
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
        $this->app->bindShared('orchestra.extension.config', function ($app) {
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
        $this->app->bindShared('orchestra.extension.finder', function ($app) {
            $config = array(
                'path.app'  => $app['path'],
                'path.base' => $app['path.base'],
            );

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

        $this->package('orchestra/extension', 'orchestra/extension', $path);
    }

    /**
     * Register extension events.
     *
     * @return void
     */
    protected function registerExtensionEvents()
    {
        $app = $this->app;

        $app->booted(function ($app) {
            $env = $app['orchestra.extension'];

            $env->attach($app['orchestra.memory']->makeOrFallback());
            $env->boot();
        });

        $app['router']->after(function () use ($app) {
            $app['orchestra.extension']->finish();
        });
    }
}
