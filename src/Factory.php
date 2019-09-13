<?php

namespace Orchestra\Extension;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Orchestra\Contracts\Extension\Dispatcher as DispatcherContract;
use Orchestra\Contracts\Extension\Factory as FactoryContract;
use Orchestra\Contracts\Extension\Finder as FinderContract;
use Orchestra\Contracts\Extension\StatusChecker as StatusCheckerContract;
use Orchestra\Contracts\Extension\UrlGenerator as UrlGeneratorContract;
use Orchestra\Extension\Bootstrap\LoadExtension;
use Orchestra\Memory\Memorizable;

class Factory implements FactoryContract
{
    use Concerns\Dispatchable,
        Concerns\Operation,
        Memorizable;

    /**
     * Application instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $app;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Dispatcher instance.
     *
     * @var \Orchestra\Contracts\Extension\Dispatcher
     */
    protected $dispatcher;

    /**
     * List of extensions.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $extensions;

    /**
     * List of routes.
     *
     * @var array
     */
    protected $routes = [];

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $app
     * @param  \Orchestra\Contracts\Extension\Dispatcher  $dispatcher
     * @param  \Orchestra\Contracts\Extension\StatusChecker  $status
     */
    public function __construct(
        Container $app,
        DispatcherContract $dispatcher,
        StatusCheckerContract $status
    ) {
        $this->app = $app;
        $this->events = $this->app->make('events');
        $this->dispatcher = $dispatcher;
        $this->extensions = new Collection();
        $this->status = $status;
    }

    /**
     * Detect all extensions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function detect(): Collection
    {
        $this->events->dispatch('orchestra.extension: detecting');

        return \tap($this->finder()->detect(), function ($extensions) {
            $this->memory->put('extensions.available', $extensions->map(static function ($item) {
                return Arr::except($item, ['description', 'author', 'url', 'version']);
            })->all());
        });
    }

    /**
     * Get extension finder.
     *
     * @return \Orchestra\Contracts\Extension\Finder
     */
    public function finder(): FinderContract
    {
        return $this->app->make('orchestra.extension.finder');
    }

    /**
     * Get an option for a given extension.
     *
     * @param  string  $name
     * @param  string  $option
     * @param  mixed   $default
     *
     * @return mixed
     */
    public function option(string $name, string $option, $default = null)
    {
        if (! $this->extensions->has($name)) {
            return \value($default);
        }

        return Arr::get($this->extensions->get($name), $option, $default);
    }

    /**
     * Check whether an extension has a writable public asset.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function permission(string $name): bool
    {
        $finder = $this->finder();
        $memory = $this->memory;
        $basePath = \rtrim($memory->get("extensions.available.{$name}.path", $name), '/');
        $path = $finder->resolveExtensionPath("{$basePath}/public");

        return $this->isWritableWithAsset($name, $path);
    }

    /**
     * Publish an extension.
     *
     * @param  string
     *
     * @return void
     */
    public function publish(string $name): void
    {
        $this->app->make('orchestra.publisher.migrate')->extension($name);
        $this->app->make('orchestra.publisher.asset')->extension($name);

        $this->events->dispatch('orchestra.publishing', [$name]);
        $this->events->dispatch("orchestra.publishing: {$name}");
    }

    /**
     * Register an extension.
     *
     * @param  string  $name
     * @param  string  $path
     *
     * @return bool
     */
    public function register(string $name, string $path): bool
    {
        return $this->finder()->registerExtension($name, $path);
    }

    /**
     * Get extension route handle.
     *
     * @param  string   $name
     * @param  string   $default
     *
     * @return \Orchestra\Contracts\Extension\UrlGenerator
     */
    public function route(string $name, string $default = '/'): UrlGeneratorContract
    {
        // Boot the extension.
        ! $this->booted() && $this->app->make(LoadExtension::class)->bootstrap($this->app);

        if (! isset($this->routes[$name])) {

            // All route should be manage via `orchestra/extension::handles.{name}`
            // config key, except for orchestra/foundation.
            $key = "orchestra/extension::handles.{$name}";

            $prefix = $this->app->make('config')->get($key, $default);

            $this->routes[$name] = $this->app->make('orchestra.extension.url')->handle($prefix);
        }

        return $this->routes[$name];
    }

    /**
     * Check whether an extension has a writable public asset.
     *
     * @param  string  $name
     * @param  string  $path
     *
     * @return bool
     */
    protected function isWritableWithAsset(string $name, string $path): bool
    {
        $files = $this->app->make('files');
        $publicPath = $this->app['path.public'];
        $targetPath = "{$publicPath}/packages/{$name}";

        if (Str::contains($name, '/') && ! $files->isDirectory($targetPath)) {
            list($vendor) = \explode('/', $name);
            $targetPath = "{$publicPath}/packages/{$vendor}";
        }

        $isWritable = $files->isWritable($targetPath);

        if ($files->isDirectory($path) && ! $isWritable) {
            return false;
        }

        return true;
    }
}
