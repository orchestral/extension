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

        $this->registerExtensionStatusChecker();

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

            $status = $app->make('orchestra.extension.status');

            return new Factory($app, $dispatcher, $status);
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
    protected function registerExtensionStatusChecker()
    {
        $this->app->singleton('orchestra.extension.status', function (Application $app) {
            return new StatusChecker($app->make('config'), $app->make('request'));
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
