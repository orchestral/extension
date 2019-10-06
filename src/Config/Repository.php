<?php

namespace Orchestra\Extension\Config;

use Illuminate\Contracts\Config\Repository as Config;
use Orchestra\Memory\MemoryManager;

class Repository
{
    /**
     * Config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Memory instance.
     *
     * @var \Orchestra\Memory\MemoryManager
     */
    protected $memory;

    /**
     * Construct a new Config Repository instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Orchestra\Memory\MemoryManager  $memory
     */
    public function __construct(Config $config, MemoryManager $memory)
    {
        $this->config = $config;
        $this->memory = $memory;
    }

    /**
     * Map configuration to allow orchestra to store it in database.
     *
     * @param  string  $name
     * @param  array   $aliases
     *
     * @return bool
     */
    public function map(string $name, array $aliases): bool
    {
        $memory = $this->memory->make();
        $meta = $memory->get("extension_{$name}", []);

        foreach ($aliases as $current => $default) {
            isset($meta[$current]) && $this->config->set($default, $meta[$current]);

            $meta[$current] = $this->config->get($default);
        }

        $memory->put("extension_{$name}", $meta);

        return true;
    }
}
