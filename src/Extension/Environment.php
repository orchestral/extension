<?php namespace Orchestra\Extension;

use Orchestra\Support\Str;
use Illuminate\Container\Container;
use Orchestra\Extension\Contracts\DebuggerInterface;
use Orchestra\Extension\Contracts\DispatcherInterface;
use Orchestra\Memory\Abstractable\Container as AbstractableContainer;

class Environment extends AbstractableContainer
{
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
     * @param  \Illuminate\Container\Container                      $app
     * @param  \Orchestra\Extension\Contracts\DispatcherInterface   $dispatcher
     * @param  \Orchestra\Extension\Contracts\DebuggerInterface     $debugger
     */
    public function __construct(Container $app, DispatcherInterface $dispatcher, DebuggerInterface $debugger)
    {
        $this->app        = $app;
        $this->dispatcher = $dispatcher;
        $this->debugger   = $debugger;
    }

    /**
     * Boot active extensions.
     *
     * @return Environment
     */
    public function boot()
    {
        // Extension should be activated only if we're not running under
        // safe mode (or debug mode). This is to ensure that developer have
        // a way to disable broken extension without tempering the database.
        if (! ($this->booted || $this->debugger->check())) {

            // Avoid extension booting being called more than once.
            $this->booted = true;

            $this->registerActiveExtensions();

            // Boot are executed once all extension has been registered. This
            // would allow extension to communicate with other extension
            // without having to known the registration dependencies.
            $this->dispatcher->boot();
        }

        return $this;
    }

    /**
     * Detect all extensions.
     *
     * @return array
     */
    public function detect()
    {
        $extensions = $this->app['orchestra.extension.finder']->detect();
        $this->memory->put('extensions.available', $extensions->all());

        return $extensions;
    }

    /**
     * Shutdown all extensions.
     *
     * @return Environment
     */
    public function finish()
    {
        foreach ($this->extensions as $name => $options) {
            $this->dispatcher->finish($name, $options);
        }

        $this->extensions = array();

        return $this;
    }

    /**
     * Activate an extension.
     *
     * @param  string   $name
     * @return bool
     */
    public function activate($name)
    {
        return $this->activating($name);
    }

    /**
     * Activating an extension.
     *
     * @param  string   $name
     * @return bool
     */
    protected function activating($name)
    {
        if (is_null($active = $this->refresh($name))) {
            return false;
        }

        $this->extensions[$name] = $active[$name];
        $this->dispatcher->register($name, $active[$name]);
        $this->publish($name);

        $this->app['events']->fire("orchestra.activating: {$name}", array($name));

        return true;
    }

    /**
     * Check whether an extension is active.
     *
     * @param  string   $name
     * @return bool
     */
    public function activated($name)
    {
        return (is_array($this->memory->get("extensions.active.{$name}")));
    }

    /**
     * Check whether an extension is available.
     *
     * @param  string   $name
     * @return bool
     */
    public function available($name)
    {
        return (is_array($this->memory->get("extensions.available.{$name}")));
    }

    /**
     * Deactivate an extension.
     *
     * @param  string   $name
     * @return bool
     */
    public function deactivate($name)
    {
        $deactivated = false;
        $memory      = $this->memory;
        $current     = $memory->get('extensions.active', array());
        $actives     = array();

        foreach ($current as $extension => $config) {
            if ($extension === $name) {
                $deactivated = true;
            } else {
                $actives[$extension] = $config;
            }
        }

        if (!! $deactivated) {
            $memory->put('extensions.active', $actives);
            $this->app['events']->fire("orchestra.deactivating: {$name}", array($name));
        }

        return $deactivated;
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
     * @return bool
     */
    public function permission($name)
    {
        $finder   = $this->app['orchestra.extension.finder'];
        $memory   = $this->memory;
        $basePath = rtrim($memory->get("extensions.available.{$name}.path", $name), '/');
        $path     = $finder->resolveExtensionPath("{$basePath}/public");

        return $this->isWritableWithAsset($name, $path);
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
        return $this->app['orchestra.extension.finder']->registerExtension($name, $path);
    }

    /**
     * Refresh extension configuration.
     *
     * @param  string   $name
     * @return array|null
     */
    public function refresh($name)
    {
        $memory    = $this->memory;
        $available = $memory->get('extensions.available', array());
        $active    = $memory->get('extensions.active', array());

        if (! isset($available[$name])) {
            return null;
        }

        // Append the activated extension to active extensions, and also
        // publish the extension (migrate the database and publish the
        // asset).
        $active[$name] = $available[$name];

        $memory->put('extensions.active', $active);

        return $active;
    }

    /**
     * Reset ectension.
     *
     * @param  string   $name
     * @return bool
     */
    public function reset($name)
    {
        $memory  = $this->memory;
        $default = $memory->get("extensions.available.{$name}", array());
        $memory->put("extensions.active.{$name}", $default);

        if ($memory->has("extension_{$name}")) {
            $memory->put("extension_{$name}", array());
        }

        return true;
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
     * Check if extension is started.
     *
     * @param  string   $name
     * @return bool
     */
    public function started($name)
    {
        return (array_key_exists($name, $this->extensions));
    }

    /**
     * Check whether an extension has a writable public asset.
     *
     * @param  string   $name
     * @param  string   $path
     * @return bool
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

    /**
     * Register all active extension to dispatcher.
     *
     * @return void
     */
    protected function registerActiveExtensions()
    {
        $memory     = $this->memory;
        $availables = $memory->get('extensions.available', array());
        $actives    = $memory->get('extensions.active', array());

        // Loop all active extension and merge the configuration with
        // available config. Extension registration is handled by dispatcher
        // process due to complexity of extension boot process.
        foreach ($actives as $name => $options) {
            if (isset($availables[$name])) {
                $config = array_merge(
                    (array) array_get($availables, "{$name}.config"),
                    (array) array_get($options, "config")
                );

                array_set($options, "config", $config);
                $this->extensions[$name] = $options;
                $this->dispatcher->register($name, $options);
            }
        }
    }
}
