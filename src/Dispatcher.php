<?php namespace Orchestra\Extension;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Config\Repository as Config;
use Orchestra\Contracts\Extension\Finder as FinderContract;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Orchestra\Contracts\Extension\Dispatcher as DispatcherContract;

class Dispatcher implements DispatcherContract
{
    /**
     * The Application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * Config Repository instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $dispatcher;

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Finder instance.
     *
     * @var \Orchestra\Contracts\Extension\Finder
     */
    protected $finder;

    /**
     * Provider instance.
     *
     * @var \Orchestra\Extension\ProviderRepository
     */
    protected $provider;

    /**
     * List of extensions to be boot.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  \Orchestra\Contracts\Extension\Finder  $finder
     * @param  \Orchestra\Extension\ProviderRepository  $provider
     */
    public function __construct(
        Application $app,
        Config $config,
        EventDispatcher $dispatcher,
        Filesystem $files,
        FinderContract $finder,
        ProviderRepository $provider
    ) {
        $this->app        = $app;
        $this->config     = $config;
        $this->dispatcher = $dispatcher;
        $this->files      = $files;
        $this->finder     = $finder;
        $this->provider   = $provider;
    }

    /**
     * Register the extension.
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    public function register($name, array $options)
    {
        $this->registerExtensionHandles($name, $options);

        // Get available service providers from orchestra.json and register
        // it to Laravel. In this case all service provider would be eager
        // loaded since the application would require it from any action.
        $this->registerExtensionProviders($options);

        $this->registerExtensionPlugin($options);

        // Register the extension so we can boot it later, this action is
        // to allow all service providers to be registered first before we
        // start the extension. An extension might be using another extension
        // to work.
        $this->extensions[$name] = $options;

        $this->start($name, $options);
    }

    /**
     * Activating the extension.
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    public function activating($name, array $options)
    {
        $this->register($name, $options);

        $this->fireEvent($name, $options, 'activating');

        $this->provider->writeFreshManifest();
    }

    /**
     * Deactivating the extension.
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    public function deactivating($name, array $options)
    {
        $this->fireEvent($name, $options, 'deactivating');

        $this->provider->writeFreshManifest();
    }

    /**
     * Set the handles to orchestra/extension package config (if available).
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    protected function registerExtensionHandles($name, array $options)
    {
        $handles = Arr::get($options, 'config.handles');

        if (! is_null($handles)) {
            $this->config->set("orchestra/extension::handles.{$name}", $handles);
        }
    }

    /**
     * Register extension service providers.
     *
     * @param  array  $options
     *
     * @return void
     */
    protected function registerExtensionProviders(array $options)
    {
        $services = Arr::get($options, 'provides', []);

        ! empty($services) && $this->provider->provides($services);
    }

    /**
     * Register extension plugin.
     *
     * @param  array  $options
     *
     * @return void
     */
    protected function registerExtensionPlugin(array $options)
    {
        $plugin = Arr::get($options, 'plugin');

        ! is_null($plugin) && $this->app->make($plugin)->bootstrap($this->app);
    }

    /**
     * Boot all extensions.
     *
     * @return void
     */
    public function boot()
    {
        foreach ($this->extensions as $name => $options) {
            $this->fireEvent($name, $options, 'booted');
        }

        $this->provider->writeManifest();
    }

    /**
     * Start the extension.
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    public function start($name, array $options)
    {
        $basePath   = rtrim($options['path'], '/');
        $sourcePath = rtrim(Arr::get($options, 'source-path', $basePath), '/');
        $autoload   = Arr::get($options, 'autoload', []);

        $search      = ['source-path::', 'app::/'];
        $replacement = ["{$sourcePath}/", 'app::'];

        // By now, extension should already exist as an extension. We should
        // be able start orchestra.php start file on each package.
        foreach ($this->getAutoloadFiles($autoload) as $filePath) {
            $this->loadAutoloaderFile(str_replace($search, $replacement, $filePath));
        }

        $this->fireEvent($name, $options, 'started');
    }

    /**
     * Shutdown an extension.
     *
     * @param  string  $name
     * @param  array   $options
     *
     * @return void
     */
    public function finish($name, array $options)
    {
        $this->fireEvent($name, $options, 'done');
    }

    /**
     * Fire events.
     *
     * @param  string  $name
     * @param  array   $options
     * @param  string  $type
     *
     * @return void
     */
    protected function fireEvent($name, $options, $type = 'started')
    {
        $this->dispatcher->fire("extension.{$type}", [$name, $options]);
        $this->dispatcher->fire("extension.{$type}: {$name}", [$options]);
    }

    /**
     * Get list of available paths for the extension.
     *
     * @param  array  $autoload
     *
     * @return array
     */
    protected function getAutoloadFiles(array $autoload)
    {
        $resolver = function ($path) {
            if (Str::contains($path, '::')) {
                return $path;
            }

            return 'source-path::'.ltrim($path, '/');
        };

        $paths = array_map($resolver, $autoload);

        return array_merge(
            $paths,
            ['source-path::src/orchestra.php', 'source-path::orchestra.php']
        );
    }

    /**
     * Load autoloader file.
     *
     * @param  string  $filePath
     *
     * @return void
     */
    protected function loadAutoloaderFile($filePath)
    {
        $filePath = $this->finder->resolveExtensionPath($filePath);

        if ($this->files->isFile($filePath)) {
            $this->files->getRequire($filePath);
        }
    }
}
