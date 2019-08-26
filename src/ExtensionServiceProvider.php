<?php

namespace Orchestra\Extension;

use Illuminate\Contracts\Container\Container;
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
        $this->app->singleton('orchestra.extension', static function (Application $app) {
            $finder = $app->make('orchestra.extension.finder');
            $provider = $app->make('orchestra.extension.provider');

            $dispatcher = new Dispatcher(
                $app, $app->make('config'), $app->make('events'), $app->make('files'), $finder, $provider
            );

            return new Factory($app, $dispatcher, $app->make('orchestra.extension.status'));
        });
    }

    /**
     * Register the service provider for Extension Config Manager.
     *
     * @return void
     */
    protected function registerExtensionConfigManager(): void
    {
        $this->app->singleton('orchestra.extension.config', static function (Container $app) {
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
        $this->app->singleton('orchestra.extension.finder', static function (Application $app) {
            return new Finder($app->make('files'), [
                'path.app' => $app->path(),
                'path.base' => $app->basePath(),
                'path.composer' => $app->basePath('/composer.lock'),
            ]);
        });
    }

    /**
     * Register the service provider for Extension Provider.
     *
     * @return void
     */
    protected function registerExtensionProvider(): void
    {
        $this->app->singleton('orchestra.extension.provider', static function (Container $app) {
            return \tap(new ProviderRepository($app, $app->make('events'), $app->make('files')), static function ($provider) {
                $provider->loadManifest();
            });
        });
    }

    /**
     * Register the service provider for Extension Safe Mode Checker.
     *
     * @return void
     */
    protected function registerExtensionStatusChecker(): void
    {
        $this->app->singleton('orchestra.extension.status', static function (Container $app) {
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
        $this->app->bind('orchestra.extension.url', static function (Container $app) {
            return new UrlGenerator($app->make('request'));
        });
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

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $path = \realpath(__DIR__.'/../');

        $this->addConfigComponent('orchestra/extension', 'orchestra/extension', "{$path}/config");
    }
}
