<?php namespace Orchestra\Extension;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Orchestra\Extension\Contracts\DebuggerInterface;
use Orchestra\Extension\Contracts\DispatcherInterface;
use Orchestra\Extension\Contracts\FactoryInterface;
use Orchestra\Extension\Traits\DispatchableTrait;
use Orchestra\Extension\Traits\OperationTrait;
use Orchestra\Memory\ContainerTrait;
use Orchestra\Support\Str;

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
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * Debugger (safe mode) instance.
     *
     * @var Debugger
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
     * @var array
     */
    protected $extensions = array();

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Container\Container $app
     * @param  Contracts\DispatcherInterface   $dispatcher
     * @param  Contracts\DebuggerInterface     $debugger
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

        return array_get($this->extensions[$name], $option, $default);
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
        $isWritable = false;

        if (Str::contains($name, '/') && ! $files->isDirectory($targetPath)) {
            list($vendor) = explode('/', $name);
            $targetPath   = "{$publicPath}/packages/{$vendor}";
            $isWritable   = $files->isWritable($targetPath);
        } else {
            $isWritable = $files->isWritable($targetPath);
        }

        if ($files->isDirectory($path) && ! $isWritable) {
            return false;
        }

        return true;
    }
}
