<?php namespace Orchestra\Extension;

use Illuminate\Container\Container;

class Dispatcher implements Contracts\DispatcherInterface
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Provider instance.
     *
     * @var ProviderRepository
     */
    protected $provider = null;

    /**
     * List of extensions to be boot.
     *
     * @var array
     */
    protected $extensions = array();

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Container\Container  $app
     * @param  ProviderRepository               $provider
     */
    public function __construct(Container $app, ProviderRepository $provider)
    {
        $this->app      = $app;
        $this->provider = $provider;
    }

    /**
     * Register the extension.
     *
     * @param  string   $name
     * @param  array    $options
     * @return void
     */
    public function register($name, array $options)
    {
        $handles = array_get($options, 'config.handles');

        // Set the handles to orchestra/extension package config (if available).
        if (! is_null($handles)) {
            $this->app['config']->set("orchestra/extension::handles.{$name}", $handles);
        }

        // Get available service providers from orchestra.json and register
        // it to Laravel. In this case all service provider would be eager
        // loaded since the application would require it from any action.
        $services = array_get($options, 'provide', array());
        ! empty($services) and $this->provider->provides($services);

        // Register the extension so we can boot it later, this action is
        // to allow all service providers to be registered first before we
        // start the extension. An extension might be using another extension
        // to work.
        $this->extensions[$name] = $options;
        $this->start($name, $options);
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
    }

    /**
     * Start the extension.
     *
     * @param  string   $name
     * @param  array    $options
     * @return void
     */
    public function start($name, $options)
    {
        $file     = $this->app['files'];
        $finder   = $this->app['orchestra.extension.finder'];
        $base     = rtrim($options['path'], '/');
        $source   = rtrim(array_get($options, 'source-path', $base), '/');
        $autoload = array_get($options, 'autoload', array());

        $generatePath = function ($path) use ($base) {
            if (str_contains($path, '::')) {
                return $path;
            }

            return "source-path::".ltrim($path, '/');
        };

        $paths = array_map($generatePath, $autoload);
        $paths = array_merge(
            $paths,
            array("source-path::src/orchestra.php", "source-path::orchestra.php")
        );

        // By now, extension should already exist as an extension. We should
        // be able start orchestra.php start file on each package.
        foreach ($paths as $path) {
            $path = str_replace(
                array('source-path::', 'app::/'),
                array("{$source}/", 'app::'),
                $path
            );

            $path = $finder->resolveExtensionPath($path);

            if ($file->isFile($path)) {
                $file->getRequire($path);
            }
        }

        $this->fireEvent($name, $options, 'started');
    }

    /**
     * Shutdown an extension.
     *
     * @param  string   $name
     * @param  array    $options
     * @return void
     */
    public function finish($name, $options)
    {
        $this->fireEvent($name, $options, 'done');
    }

    /**
     * Fire events.
     *
     * @param  string   $name
     * @param  array    $options
     * @param  string   $type
     * @return void
     */
    protected function fireEvent($name, $options, $type = 'started')
    {
        $this->app['events']->fire("extension.{$type}", array($name, $options));
        $this->app['events']->fire("extension.{$type}: {$name}", array($options));
    }
}
