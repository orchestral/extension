<?php namespace Orchestra\Extension;

use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Contracts\Extension\Finder as FinderContract;

class Finder implements FinderContract
{
    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Application and base path configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * List of paths.
     *
     * @var array
     */
    protected $paths = [];

    /**
     * Default manifest options.
     *
     * @var array
     */
    protected $manifestOptions =  [
        'name'        => null,
        'description' => null,
        'author'      => null,
        'url'         => null,
        'version'     => '>0',
        'config'      => [],
        'autoload'    => [],
        'provide'     => [],
    ];

    /**
     * List of reserved name.
     *
     * @var array
     */
    protected $reserved = [
        'orchestra',
        'resources',
        'orchestra/asset',
        'orchestra/auth',
        'orchestra/debug',
        'orchestra/extension',
        'orchestra/facile',
        'orchestra/foundation',
        'orchestra/html',
        'orchestra/memory',
        'orchestra/messages',
        'orchestra/model',
        'orchestra/notifier',
        'orchestra/optimize',
        'orchestra/platform',
        'orchestra/resources',
        'orchestra/support',
        'orchestra/testbench',
        'orchestra/view',
        'orchestra/widget',
    ];

    /**
     * Construct a new finder.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  array  $config
     */
    public function __construct(Filesystem $files, array $config)
    {
        $this->files  = $files;
        $this->config = $config;

        $app  = rtrim($config['path.app'], '/');
        $base = rtrim($config['path.base'], '/');

        // In most cases we would only need to concern with the following
        // path; application folder, vendor folders and workbench folders.
        $this->paths = [
            "{$app}",
            "{$base}/vendor/*/*",
            "{$base}/workbench/*/*"
        ];
    }

    /**
     * Add a new path to finder.
     *
     * @param  string  $path
     * @return $this
     */
    public function addPath($path)
    {
        $path = rtrim($path, '/');

        if (! in_array($path, $this->paths)) {
            $this->paths[] = $path;
        }

        return $this;
    }

    /**
     * Detect available extensions.
     *
     * @return \Illuminate\Support\Collection
     */
    public function detect()
    {
        $extensions = [];

        // Loop each path to check if there orchestra.json available within
        // the paths. We would only treat packages that include orchestra.json
        // as an Orchestra Platform extension.
        foreach ($this->paths as $key => $path) {
            $manifests = $this->files->glob($this->resolveExtensionPath("{$path}/orchestra.json"));

            // glob() method might return false if there an errors, convert
            // the result to an array.
            is_array($manifests) || $manifests = [];

            foreach ($manifests as $manifest) {
                $name = (is_numeric($key) ? $this->guessExtensionNameFromManifest($manifest, $path) : $key);

                if (! is_null($name)) {
                    $extensions[$name] = $this->getManifestContents($manifest);
                }
            }
        }

        return new Collection($extensions);
    }

    /**
     * Get manifest contents.
     *
     * @param  string  $manifest
     * @return array
     * @throws \Orchestra\Extension\ManifestRuntimeException
     */
    protected function getManifestContents($manifest)
    {
        $path     = $sourcePath = $this->guessExtensionPath($manifest);
        $jsonable = json_decode($this->files->get($manifest), true);

        // If json_decode fail, due to invalid json format. We going to
        // throw an exception so this error can be fixed by the developer
        // instead of allowing the application to run with a buggy config.
        if (is_null($jsonable)) {
            throw new ManifestRuntimeException("Cannot decode file [{$manifest}]");
        }

        isset($jsonable['path']) && $path = $jsonable['path'];

        $paths = [
            'path'        => rtrim($path, '/'),
            'source-path' => rtrim($sourcePath, '/'),
        ];

        // Generate a proper manifest configuration for the extension. This
        // would allow other part of the application to use this configuration
        // to migrate, load service provider as well as preload some
        // configuration.
        return array_merge($paths, $this->generateManifestConfig($jsonable));
    }

    /**
     * Generate a proper manifest configuration for the extension. This
     * would allow other part of the application to use this configuration
     * to migrate, load service provider as well as preload some
     * configuration.
     *
     * @param  array  $jsonable
     * @return array
     */
    protected function generateManifestConfig(array $jsonable)
    {
        $manifest = [];

        // Assign extension manifest option or provide the default value.
        foreach ($this->manifestOptions as $key => $default) {
            $manifest["{$key}"] = Arr::get($jsonable, $key, $default);
        }

        return $manifest;
    }

    /**
     * Guess extension name from manifest.
     *
     * @param  string  $manifest
     * @param  string  $path
     * @return string
     * @throws \RuntimeException
     */
    public function guessExtensionNameFromManifest($manifest, $path)
    {
        if (rtrim($this->config['path.app'], '/') === rtrim($path, '/')) {
            return 'app';
        }

        list($vendor, $package) = $namespace = $this->resolveExtensionNamespace($manifest);

        if (is_null($vendor) && is_null($package)) {
            return null;
        }

        // Each package should have vendor/package name pattern.
        $name = trim(implode('/', $namespace));

        return $this->validateExtensionName($name);
    }

    /**
     * Guess extension path from manifest file.
     *
     * @param  string  $path
     * @return string
     */
    public function guessExtensionPath($path)
    {
        $path = str_replace('orchestra.json', '', $path);
        $app  = rtrim($this->config['path.app'], '/');
        $base = rtrim($this->config['path.base'], '/');

        return str_replace(
            ["{$app}/", "{$base}/vendor/", "{$base}/workbench/", "{$base}/"],
            ['app::', 'vendor::', 'workbench::', 'base::'],
            $path
        );
    }

    /**
     * Register the extension.
     *
     * @param  string  $name
     * @param  string  $path
     * @return bool
     */
    public function registerExtension($name, $path)
    {
        $this->paths[$name] = rtrim($path, '/');

        return true;
    }

    /**
     * Resolve extension namespace name from manifest.
     *
     * @param  string  $manifest
     * @return array
     */
    public function resolveExtensionNamespace($manifest)
    {
        $vendor   = null;
        $package  = null;
        $manifest = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $manifest);
        $fragment = explode(DIRECTORY_SEPARATOR, $manifest);

        // Remove orchestra.json from fragment as we are only interested with
        // the two segment before it.
        if (array_pop($fragment) == 'orchestra.json') {
            $package = array_pop($fragment);
            $vendor  = array_pop($fragment);
        }

        return [$vendor, $package];
    }

    /**
     * Resolve extension path.
     *
     * @param  string  $path
     * @return string
     */
    public function resolveExtensionPath($path)
    {
        $app  = rtrim($this->config['path.app'], '/');
        $base = rtrim($this->config['path.base'], '/');

        return str_replace(
            ['app::', 'vendor::', 'workbench::', 'base::'],
            ["{$app}/", "{$base}/vendor/", "{$base}/workbench/", "{$base}/"],
            $path
        );
    }

    /**
     * Validate extension name.
     *
     * @param  string  $name
     * @return string
     * @throws \RuntimeException
     */
    public function validateExtensionName($name)
    {
        if (in_array($name, $this->reserved)) {
            throw new RuntimeException("Unable to register reserved name [{$name}] as extension.");
        }

        return $name;
    }
}
