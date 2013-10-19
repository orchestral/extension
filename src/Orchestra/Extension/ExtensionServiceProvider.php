<?php namespace Orchestra\Extension;

use Exception;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class ExtensionServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerExtension();
        $this->registerExtensionConfigManager();
        $this->registerExtensionFinder();
        $this->registerAliases();
        $this->registerExtensionEvents();
    }

    /**
     * Register the service provider for Extension.
     *
     * @return void
     */
    protected function registerExtension()
    {
        $this->app['orchestra.extension'] = $this->app->share(function ($app) {
            $provider   = new ProviderRepository($app);
            $dispatcher = new Dispatcher($app, $provider);
            $debugger   = new Debugger($app);

            return new Environment($app, $dispatcher, $debugger);
        });
    }

    /**
     * Register the service provider for Extension Config Manager.
     *
     * @return void
     */
    protected function registerExtensionConfigManager()
    {
        $this->app['orchestra.extension.config'] = $this->app->share(function ($app) {
            return new ConfigManager($app);
        });
    }

    /**
     * Register the service provider for Extension Finder.
     *
     * @return void
     */
    protected function registerExtensionFinder()
    {
        $this->app['orchestra.extension.finder'] = $this->app->share(function ($app) {
            return new Finder($app);
        });
    }

    /**
     * Register aliases.
     *
     * @return void
     */
    protected function registerAliases()
    {
        $this->app->booting(function () {
            $loader = AliasLoader::getInstance();
            $loader->alias('Orchestra\Extension', 'Orchestra\Support\Facades\Extension');
            $loader->alias('Orchestra\Config', 'Orchestra\Support\Facades\Config');
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = realpath(__DIR__.'/../../');

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

        $app->after(function () use ($app) {
            $app['orchestra.extension']->finish();
        });
    }
}
