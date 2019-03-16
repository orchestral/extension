<?php

namespace Orchestra\Extension\Concerns;

use Illuminate\Support\Arr;

trait Operation
{
    /**
     * Activate an extension.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function activate(string $name): bool
    {
        return $this->activating($name);
    }

    /**
     * Activating an extension.
     *
     * @param  string  $name
     *
     * @return bool
     */
    protected function activating(string $name): bool
    {
        if (\is_null($active = $this->refresh($name))) {
            return false;
        }

        $this->extensions->put($name, $active[$name]);
        $this->publish($name);

        $this->dispatcher->activating($name, $active[$name]);

        return true;
    }

    /**
     * Check whether an extension is active.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function activated(string $name): bool
    {
        return \is_array($this->memory->get("extensions.active.{$name}"));
    }

    /**
     * Check whether an extension is available.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function available(string $name): bool
    {
        return \is_array($this->memory->get("extensions.available.{$name}"));
    }

    /**
     * Deactivate an extension.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function deactivate(string $name): bool
    {
        $memory = $this->memory;
        $active = $memory->get('extensions.active', []);

        if (! isset($active[$name])) {
            return false;
        }

        $memory->put('extensions.active', Arr::except($active, $name));
        $this->dispatcher->deactivating($name, $active[$name]);

        return true;
    }

    /**
     * Refresh extension configuration.
     *
     * @param  string  $name
     *
     * @return array|null
     */
    public function refresh(string $name): ?array
    {
        $memory = $this->memory;
        $available = $memory->get('extensions.available', []);
        $active = $memory->get('extensions.active', []);

        if (! isset($available[$name])) {
            return null;
        }

        // Append the activated extension to active extensions, and also
        // publish the extension (migrate the database and publish the
        // asset).
        if (! \is_null($handles = $active[$name]['config']['handles'] ?? null)) {
            Arr::set($available, "{$name}.config.handles", $handles);
        }

        $active[$name] = $available[$name];

        $memory->put('extensions.active', $active);

        return $active;
    }

    /**
     * Reset extension.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function reset(string $name): bool
    {
        $memory = $this->memory;
        $default = $memory->get("extensions.available.{$name}", []);

        $memory->put("extensions.active.{$name}", $default);

        if ($memory->has("extension_{$name}")) {
            $memory->put("extension_{$name}", []);
        }

        return true;
    }

    /**
     * Check if extension is started.
     *
     * @param  string  $name
     *
     * @return bool
     */
    public function started(string $name): bool
    {
        return $this->extensions->has($name);
    }

    /**
     * Publish an extension.
     *
     * @param  string
     *
     * @return void
     */
    abstract public function publish(string $name): void;
}
