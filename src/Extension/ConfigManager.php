<?php namespace Orchestra\Extension;

use Illuminate\Contracts\Config\Config;
use Orchestra\Memory\MemoryManager;

class ConfigManager
{
    /**
     * Config instance.
     *
     * @var \Illuminate\Contracts\Config\Config
     */
    protected $config;

    /**
     * Memory instance.
     *
     * @var \Orchestra\Memory\MemoryManager
     */
    protected $memory;

    /**
     * Construct a new ConfigManager instance.
     *
     * @param  \Illuminate\Contracts\Config\Config  $config
     * @param  \Orchestra\Memory\MemoryManager      $memory
     */
    public function __construct(Config $config, MemoryManager $memory)
    {
        $this->config = $config;
        $this->memory = $memory;
    }

    /**
     * Map configuration to allow orchestra to store it in database.
     *
     * @param  string   $name
     * @param  array    $aliases
     * @return boolean
     */
    public function map($name, $aliases)
    {
        $memory = $this->memory->make();
        $meta   = $memory->get("extension_{$name}", array());

        foreach ($aliases as $current => $default) {
            isset($meta[$current]) && $this->config->set($default, $meta[$current]);

            $meta[$current] = $this->config->get($default);
        }

        $memory->put("extension_{$name}", $meta);

        return true;
    }
}
