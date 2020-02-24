<?php

namespace Orchestra\Extension;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Fluent;
use Orchestra\Contracts\Html\Form\Builder as FormBuilder;

abstract class Plugin
{
    /**
     * Extension name.
     *
     * @var string
     */
    protected $extension;

    /**
     * Configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Menu handler.
     *
     * @var object|null
     */
    protected $menu;

    /**
     * Validation rules.
     *
     * @var array
     */
    protected $rules = [];

    /**
     * Sidebar placeholders.
     *
     * @var array
     */
    protected $sidebar = [];

    /**
     * Bootstrap plugin.
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $this->bootstrapConfiguration($app);

        $this->bootstrapForm($app);

        $this->bootstrapMenuHandler($app);

        $this->bootstrapSidebarPlaceholders($app);

        $this->bootstrapValidationRules($app);
    }

    /**
     * Bootstrap configuration.
     *
     * @return void
     */
    protected function bootstrapConfiguration(Application $app)
    {
        if (empty($this->extension) || empty($this->config)) {
            return;
        }

        $app->make('orchestra.extension.config')->map($this->extension, $this->config);
    }

    /**
     * Bootstrap the form.
     *
     * @return void
     */
    protected function bootstrapForm(Application $app)
    {
        $this->attachListenerOn($app, 'form', function (Fluent $model, FormBuilder $form) {
            $this->form($model, $form);
        });
    }

    /**
     * Bootstrap menu handler.
     *
     * @return void
     */
    protected function bootstrapMenuHandler(Application $app)
    {
        if (\is_null($this->menu)) {
            return;
        }

        $app->make('events')->listen('orchestra.ready: admin', $this->menu);
    }

    /**
     * Bootstrap sidebar placeholder.
     *
     * @return void
     */
    protected function bootstrapSidebarPlaceholders(Application $app)
    {
        $widget = $app->make('orchestra.widget');
        $placeholder = $widget->make('placeholder.orchestra.extensions');

        $this->attachListenerOn($app, 'form', function () use ($placeholder) {
            foreach ($this->sidebar as $name => $view) {
                $placeholder->add($name)->value(\view($view));
            }
        });
    }

    /**
     * Bootstrap validation rules.
     *
     * @return void
     */
    protected function bootstrapValidationRules(Application $app)
    {
        $this->attachListenerOn($app, 'validate', function (Fluent $rules) {
            foreach ($this->rules as $name => $validation) {
                $rules[$name] = $validation;
            }
        });
    }

    /**
     * Attach event listener.
     *
     * @param  string  $event
     *
     * @return void
     */
    protected function attachListenerOn(Application $app, $event, Closure $callback)
    {
        $app->make('events')->listen("orchestra.{$event}: extension.{$this->extension}", $callback);
    }

    /**
     * Setup the form.
     *
     * @return void
     */
    abstract protected function form(Fluent $model, FormBuilder $form);
}
