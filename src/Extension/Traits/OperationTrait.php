<?php namespace Orchestra\Extension\Traits;

trait OperationTrait
{
    /**
     * Activate an extension.
     *
     * @param  string   $name
     * @return boolean
     */
    public function activate($name)
    {
        if ($this->memory->has("extensions.available.{$name}")) {
            return $this->activating($name);
        }

        return false;
    }

    /**
     * Activating an extension.
     *
     * @param  string   $name
     * @return boolean
     */
    protected function activating($name)
    {
        $memory    = $this->memory;
        $available = $memory->get('extensions.available', array());
        $active    = $memory->get('extensions.active', array());

        // Append the activated extension to active extensions, and also
        // publish the extension (migrate the database and publish the
        // asset).
        $this->extensions[$name] = $active[$name] = $available[$name];
        $this->dispatcher->register($name, $active[$name]);
        $this->publish($name);

        $memory->put('extensions.active', $active);

        $this->app['events']->fire("orchestra.activating: {$name}", array($name));

        return true;
    }

    /**
     * Check whether an extension is active.
     *
     * @param  string   $name
     * @return boolean
     */
    public function activated($name)
    {
        $memory = $this->memory;
        return (is_array($memory->get("extensions.active.{$name}")));
    }

    /**
     * Check whether an extension is available.
     *
     * @param  string   $name
     * @return boolean
     */
    public function available($name)
    {
        $memory = $this->memory;
        return (is_array($memory->get("extensions.available.{$name}")));
    }

    /**
     * Deactivate an extension.
     *
     * @param  string   $name
     * @return boolean
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
     * Reset extension.
     *
     * @param  string   $name
     * @return boolean
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
     * Check if extension is started.
     *
     * @param  string   $name
     * @return boolean
     */
    public function started($name)
    {
        return (array_key_exists($name, $this->extensions));
    }
}
