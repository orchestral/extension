<?php namespace Orchestra\Extension;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Orchestra\Contracts\Foundation\DeferrableServiceContainer;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcherContract;

class ProviderRepository
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
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
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(Application $app, EventDispatcherContract $events, Filesystem $files)
    {
        $this->app    = $app;
        $this->events = $events;
        $this->files  = $files;

        $this->manifestPath = dirname($this->app->getCachedServicesPath()).'/extension.json';
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
        $services = [];

        foreach ($provides as $provider) {
            if (! isset($this->manifest[$provider])) {
                $services[$provider] = $this->recompileProvider($provider);
            } else {
                $services[$provider] = $this->manifest[$provider];
            }
        }

        $this->dispatch($services);
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
        $instance = $this->app->resolveProviderClass($provider);

        $type = $instance->isDeferred() && $this->app instanceof DeferrableServiceContainer ? 'Deferred' : 'Eager';

        return $this->{"register{$type}ServiceProvider"}($provider, $instance);
    }

    /**
     * Register all deferred service providers.
     *
     * @return void
     */
    protected function dispatch(array $services)
    {
        foreach ($services as $provider => $options) {
            $this->loadDeferredServiceProvider($provider, $options);
            $this->loadEagerServiceProvider($provider, $options);
            $this->loadQueuedServiceProvider($provider, $options);

            unset($options['instance']);

            $this->compiled[$provider] = $options;
        }
    }

    /**
     * Load the service provider manifest JSON file.
     *
     * @return array
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.
        if (! $this->files->exists($this->manifestPath)) {
            return $this->manifest = [];
        }

        return $this->manifest = json_decode($this->files->get($this->manifestPath), true);
    }

    /**
     * Determine if the manifest should be compiled.
     *
     * @return bool
     */
    public function shouldRecompile()
    {
        return array_keys($this->manifest) != array_keys($this->compiled);
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
        $this->files->put($this->manifestPath, json_encode($manifest, JSON_PRETTY_PRINT));
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
        $deferred = [];

        foreach ($instance->provides() as $provide) {
            $deferred[$provide] = $provider;
        }

        return [
            'instance' => $instance,
            'eager'    => false,
            'when'     => $instance->when(),
            'deferred' => $deferred,
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
            'eager'    => true,
            'when'     => [],
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
            return ;
        }

        if (! $this->app instanceof DeferrableServiceContainer) {
            return $this->app->register($provider);
        }

        $this->app->setDeferredServices(array_merge($this->app->getDeferredServices(), $options['deferred']));
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
            return ;
        }

        $this->app->register(isset($options['instance']) ? $options['instance'] : $provider);
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
        foreach ($options['when'] as $listen) {
            $this->events->listen($listen, function () use ($provider, $options) {
                $this->app->register(isset($options['instance']) ? $options['instance'] : $provider);
            });
        }
    }
}
