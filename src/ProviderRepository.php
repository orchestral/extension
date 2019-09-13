<?php

namespace Orchestra\Extension;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcherContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Orchestra\Contracts\Foundation\Application;

class ProviderRepository
{
    /**
     * Application instance.
     *
     * @var \Orchestra\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * List of compiled services.
     *
     * @var array
     */
    protected $compiled = [];

    /**
     * List of cached manifest.
     *
     * @var array
     */
    protected $manifest = [];

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Construct a new finder.
     *
     * @param  \Orchestra\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Application $app, EventDispatcherContract $events, Filesystem $files)
    {
        $this->app = $app;
        $this->events = $events;
        $this->files = $files;

        $this->manifestPath = $this->app->getCachedExtensionServicesPath();
    }

    /**
     * Load available service providers.
     *
     * @param  array  $provides
     *
     * @return void
     */
    public function provides(array $provides)
    {
        $this->compiled = Collection::make($provides)->mapWithKeys(function ($provider) {
            $options = $this->manifest[$provider] ?? $this->recompileProvider($provider);

            $this->loadDeferredServiceProvider($provider, $options);
            $this->loadEagerServiceProvider($provider, $options);
            $this->loadQueuedServiceProvider($provider, $options);

            return [$provider => Arr::except($options, ['instance'])];
        })->all();
    }

    /**
     * Recompile provider by reviewing the class configuration.
     *
     * @param  string  $provider
     *
     * @return array
     */
    protected function recompileProvider($provider)
    {
        $instance = $this->app->resolveProvider($provider);

        $type = $instance->isDeferred() ? 'Deferred' : 'Eager';

        return $this->{"register{$type}ServiceProvider"}($provider, $instance);
    }

    /**
     * Load the service provider manifest JSON file.
     *
     * @return array
     */
    public function loadManifest()
    {
        $this->manifest = [];

        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
        if ($this->files->exists($this->manifestPath)) {
            return $this->manifest = $this->files->getRequire($this->manifestPath);
        }

        return $this->manifest;
    }

    /**
     * Determine if the manifest should be compiled.
     *
     * @return bool
     */
    public function shouldRecompile()
    {
        return \array_keys($this->manifest) != \array_keys($this->compiled);
    }

    /**
     * Write the service manifest file to disk.
     *
     * @return array
     */
    public function writeManifest()
    {
        if ($this->shouldRecompile()) {
            $this->writeManifestFile($this->compiled);
        }
    }

    /**
     * Write an empty service manifest file to disk.
     *
     * @return array
     */
    public function writeFreshManifest()
    {
        $this->writeManifestFile($this->manifest = []);
    }

    /**
     * Write the manifest file.
     *
     * @param  array  $manifest
     *
     * @return void
     */
    protected function writeManifestFile(array $manifest = [])
    {
        $this->files->put($this->manifestPath, '<?php return '.\var_export($manifest, true).';');
    }

    /**
     * Register deferred service provider.
     *
     * @param  string  $provider
     * @param  \Illuminate\Support\ServiceProvider  $instance
     *
     * @return void
     */
    protected function registerDeferredServiceProvider($provider, ServiceProvider $instance)
    {
        return [
            'instance' => $instance,
            'eager' => false,
            'when' => $instance->when(),
            'deferred' => \array_fill_keys($instance->provides(), $provider),
        ];
    }

    /**
     * Register eager service provider.
     *
     * @param  string  $provider
     * @param  \Illuminate\Support\ServiceProvider  $instance
     *
     * @return void
     */
    protected function registerEagerServiceProvider($provider, ServiceProvider $instance)
    {
        return [
            'instance' => $instance,
            'eager' => true,
            'when' => [],
            'deferred' => [],
        ];
    }

    /**
     * Load deferred service provider.
     *
     * @param  string  $provider
     * @param  array  $options
     *
     * @return void
     */
    protected function loadDeferredServiceProvider($provider, array $options)
    {
        if ($options['eager']) {
            return;
        }

        $this->app->addDeferredServices($options['deferred']);
    }

    /**
     * Load eager service provider.
     *
     * @param  string  $provider
     * @param  array  $options
     *
     * @return void
     */
    protected function loadEagerServiceProvider($provider, array $options)
    {
        if (! $options['eager']) {
            return;
        }

        $this->app->register($options['instance'] ?? $provider);
    }

    /**
     * Load queued service provider.
     *
     * @param  string  $provider
     * @param  array  $options
     *
     * @return void
     */
    protected function loadQueuedServiceProvider($provider, array $options)
    {
        $app = $this->app;

        foreach ($options['when'] as $listen) {
            $this->events->listen($listen, static function () use ($app, $provider, $options) {
                $app->register($options['instance'] ?? $provider);
            });
        }
    }
}
