<?php namespace Orchestra\Extension;

use Illuminate\Container\Container;

class ConfigManager
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Construct a new ConfigManager instance.
     *
     * @param  \Illuminate\Container\Container  $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Map configuration to allow orchestra to store it in database.
     *
     * @param  string   $name
     * @param  array    $maps
     * @return void
     */
    public function map($name, $maps)
    {
        $config = $this->app['config'];
        $memory = $this->app['orchestra.memory']->make();
        $meta   = $memory->get("extension_{$name}", array());

        foreach ($maps as $current => $default) {
            isset($meta[$current]) and $config->set($default, $meta[$current]);

            $meta[$current] = $config->get($default);
        }

        $memory->put("extension_{$name}", $meta);
    }
}
