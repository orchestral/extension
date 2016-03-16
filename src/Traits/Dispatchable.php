<?php namespace Orchestra\Extension\Traits;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

trait Dispatchable
{
    /**
     * Booted indicator.
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The status checker implementation.
     *
     * @var \Orchestra\Contracts\Extension\StatusChecker
     */
    protected $status;

    /**
     * Boot active extensions.
     *
     * @return $this
     */
    public function boot()
    {
        // Extension should be activated only if we're not running under
        // safe mode (or debug mode). This is to ensure that developer have
        // a way to disable broken extension without tempering the database.
        if (! ($this->booted() || $this->status->is('safe'))) {
            // Avoid extension booting being called more than once.
            $this->booted = true;

            $this->registerActiveExtensions();

            // Boot are executed once all extension has been registered. This
            // would allow extension to communicate with other extension
            // without having to known the registration dependencies.
            $this->dispatcher->boot();

            $this->events->fire('orchestra.extension: booted');
        }

        return $this;
    }

    /**
     * Boot active extensions.
     *
     * @return $this
     */
    public function booted()
    {
        return $this->booted;
    }

    /**
     * Shutdown all extensions.
     *
     * @return $this
     */
    public function finish()
    {
        foreach ($this->extensions as $name => $options) {
            $this->dispatcher->finish($name, $options);
        }

        $this->extensions = new Collection();

        return $this;
    }

    /**
     * Register all active extension to dispatcher.
     *
     * @return void
     */
    protected function registerActiveExtensions()
    {
        $available = $this->memory->get('extensions.available', []);
        $active    = $this->memory->get('extensions.active', []);

        // Loop all active extension and merge the configuration with
        // available config. Extension registration is handled by dispatcher
        // process due to complexity of extension boot process.
        foreach ($active as $name => $options) {
            if (isset($available[$name])) {
                $config = array_merge(
                    (array) Arr::get($available, "{$name}.config"),
                    (array) Arr::get($options, 'config')
                );

                Arr::set($options, 'config', $config);

                $this->extensions[$name] = $options;
                $this->dispatcher->register($name, $options);
            }
        }
    }

    /**
     * Create an event listener or execute it directly.
     *
     * @param  \Closure|null  $callback
     *
     * @return void
     */
    public function after(Closure $callback = null)
    {
        if ($this->booted() || $this->status->is('safe')) {
            return $this->app->call($callback);
        }

        $this->events->listen('orchestra.extension: booted', $callback);
    }
}
