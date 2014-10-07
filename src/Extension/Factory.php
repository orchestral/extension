<?php namespace Orchestra\Extension;

use Orchestra\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Container\Container;
use Orchestra\Memory\ContainerTrait;
use Orchestra\Extension\Traits\OperationTrait;
use Orchestra\Extension\Traits\DispatchableTrait;
use Orchestra\Extension\Contracts\FactoryInterface;
use Orchestra\Extension\Contracts\DebuggerInterface;
use Orchestra\Extension\Contracts\DispatcherInterface;

class Factory implements FactoryInterface
{
    use ContainerTrait, DispatchableTrait, OperationTrait;

    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * Dispatcher instance.
     *
     * @var \Orchestra\Extension\Contracts\DispatcherInterface
     */
    protected $dispatcher;

    /**
     * Debugger (safe mode) instance.
     *
     * @var \Orchestra\Extension\Contracts\DebuggerInterface
     */
    protected $debugger;

    /**
     * Booted indicator.
     *
     * @var boolean
     */
    protected $booted = false;

    /**
     * List of extensions.
     *
     * @var \Illuminate\Support\Collection
     */
    protected $extensions;

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Container\Container                      $app
     * @param  \Orchestra\Extension\Contracts\DispatcherInterface   $dispatcher
     * @param  \Orchestra\Extension\Contracts\DebuggerInterface     $debugger
     */
    public function __construct(Container $app, DispatcherInterface $dispatcher, DebuggerInterface $debugger)
    {
        $this->app        = $app;
        $this->dispatcher = $dispatcher;
        $this->debugger   = $debugger;
        $this->extensions = new Collection;
    }

    /**
     * Detect all extensions.
     *
     * @return array
     */
    public function detect()
    {
        $extensions = $this->finder()->detect();
        $this->memory->put('extensions.available', $extensions->all());

        return $extensions;
    }

    /**
     * Get extension finder.
     *
     * @return \Orchestra\Extension\Finder
     */
    public function finder()
    {
        return $this->app['orchestra.extension.finder'];
    }

    /**
     * Get an option for a given extension.
     *
     * @param  string   $name
     * @param  string   $option
     * @param  mixed    $default
     * @return mixed
     */
    public function option($name, $option, $default = null)
    {
        if (! isset($this->extensions[$name])) {
            return value($default);
        }

        return Arr::get($this->extensions[$name], $option, $default);
    }

    /**
     * Check whether an extension has a writable public asset.
     *
     * @param  string   $name
     * @return boolean
     */
    public function permission($name)
    {
        $finder   = $this->finder();
        $memory   = $this->memory;
        $basePath = rtrim($memory->get("extensions.available.{$name}.path", $name), '/');
        $path     = $finder->resolveExtensionPath("{$basePath}/public");

        return $this->isWritableWithAsset($name, $path);
    }

    /**
     * Publish an extension.
     *
     * @param  string
     * @return void
     */
    public function publish($name)
    {
        $this->app['orchestra.publisher.migrate']->extension($name);
        $this->app['orchestra.publisher.asset']->extension($name);

        $this->app['events']->fire("orchestra.publishing", array($name));
        $this->app['events']->fire("orchestra.publishing: {$name}");
    }

    /**
     * Register an extension.
     *
     * @param  string   $name
     * @param  string   $path
     * @return bool
     */
    public function register($name, $path)
    {
        return $this->finder()->registerExtension($name, $path);
    }

    /**
     * Get extension route handle.
     *
     * @param  string   $name
     * @param  string   $default
     * @return string
     */
    public function route($name, $default = '/')
    {
        // Boot the extension.
        $this->boot();

        // All route should be manage via `orchestra/extension::handles.{name}`
        // config key, except for orchestra/foundation.
        $key = "orchestra/extension::handles.{$name}";

        return new RouteGenerator(
            $this->app['config']->get($key, $default),
            $this->app['request']
        );
    }

    /**
     * Check whether an extension has a writable public asset.
     *
     * @param  string   $name
     * @param  string   $path
     * @return boolean
     */
    protected function isWritableWithAsset($name, $path)
    {
        $files      = $this->app['files'];
        $publicPath = $this->app['path.public'];
        $targetPath = "{$publicPath}/packages/{$name}";

        if (Str::contains($name, '/') && ! $files->isDirectory($targetPath)) {
            list($vendor) = explode('/', $name);
            $targetPath   = "{$publicPath}/packages/{$vendor}";
        }

        $isWritable = $files->isWritable($targetPath);

        if ($files->isDirectory($path) && ! $isWritable) {
            return false;
        }

        return true;
    }
}
