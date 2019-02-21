<?php

namespace Orchestra\Extension;

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
        $this->registerExtensionConfigManager();

        $this->registerExtensionFinder();

        $this->registerExtensionProvider();

        $this->registerExtensionStatusChecker();

        $this->registerExtensionUrlGenerator();

        $this->registerExtension();

        $this->registerExtensionEvents();
    }

    /**
     * Register the service provider for Extension.
     *
     * @return void
     */
    protected function registerExtension(): void
    {
        $this->app->singleton('orchestra.extension', function (Application $app) {
            $config = $app->make('config');
            $events = $app->make('events');
            $files = $app->make('files');
            $finder = $app->make('orchestra.extension.finder');
            $status = $app->make('orchestra.extension.status');
            $provider = $app->make('orchestra.extension.provider');

            $dispatcher = new Dispatcher($app, $config, $events, $files, $finder, $provider);

            return new Factory($app, $dispatcher, $status);
        });
    }

    /**
     * Register the service provider for Extension Config Manager.
     *
     * @return void
     */
    protected function registerExtensionConfigManager(): void
    {
        $this->app->singleton('orchestra.extension.config', function (Application $app) {
            return new Config\Repository($app->make('config'), $app->make('orchestra.memory'));
        });
    }

    /**
     * Register the service provider for Extension Finder.
     *
     * @return void
     */
    protected function registerExtensionFinder(): void
    {
        $this->app->singleton('orchestra.extension.finder', function (Application $app) {
            $config = [
                'path.app' => $app->path(),
                'path.base' => $app->basePath(),
                'path.composer' => $app->basePath('/composer.lock'),
            ];

            return new Finder($app->make('files'), $config);
        });
    }

    /**
     * Register the service provider for Extension Provider.
     *
     * @return void
     */
    protected function registerExtensionProvider(): void
    {
        $this->app->singleton('orchestra.extension.provider', function (Application $app) {
            $provider = new ProviderRepository($app, $app->make('events'), $app->make('files'));

            $provider->loadManifest();

            return $provider;
        });
    }

    /**
     * Register the service provider for Extension Safe Mode Checker.
     *
     * @return void
     */
    protected function registerExtensionStatusChecker(): void
    {
        $this->app->singleton('orchestra.extension.status', function (Application $app) {
            return new StatusChecker($app->make('config'), $app->make('request'));
        });
    }

    /**
     * Register the service provider for Extension Provider.
     *
     * @return void
     */
    protected function registerExtensionUrlGenerator(): void
    {
        $this->app->bind('orchestra.extension.url', function (Application $app) {
            return new UrlGenerator($app->make('request'));
        });
    }

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = \realpath(__DIR__.'/../');

        $this->addConfigComponent('orchestra/extension', 'orchestra/extension', "{$path}/resources/config");
    }

    /**
     * Register extension events.
     *
     * @return void
     */
    protected function registerExtensionEvents(): void
    {
        $this->app->terminating(function () {
            $this->app->make('orchestra.extension')->finish();
        });
    }
}
