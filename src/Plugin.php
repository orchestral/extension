<?php namespace Orchestra\Extension;

use Illuminate\Support\Fluent;
use Illuminate\Contracts\Foundation\Application;
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
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    public function bootstrap(Application $app)
    {
        $this->bootstrapConfiguration($app);

        $this->bootstrapMenuHandler($app);

        $this->bootstrapSidebarPlaceholders($app);

        $this->bootstrapValidationRules($app);
    }

    /**
     * Bootstrap configuration.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    protected function bootstrapConfiguration(Application $app)
    {
        $app->make('orchestra.extension.config')->map($this->extension, $this->config);
    }

    /**
     * Bootstrap menu handler.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    protected function bootstrapMenuHandler(Application $app)
    {
        if (! is_null($this->menu)) {
            $app->make('events')
                ->listen('orchestra.ready: admin', $this->menu);
        }
    }

    /**
     * Bootstrap sidebar placeholder.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    protected function bootstrapSidebarPlaceholders(Application $app)
    {
        $widget      = $app->make('orchestra.widget');
        $placeholder = $widget->make('placeholder.orchestra.extensions');

        foreach ($sidebar as $name => $view) {
            $placeholder->add($name)->value(view($view));
        }
    }

    /**
     * Bootstrap validation rules.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     *
     * @return void
     */
    protected function bootstrapValidationRules(Application $app)
    {
        $app->make('events')
            ->listen('orchestra.validate: extension.orchestra/story', function (Fluent $rules) {
                foreach ($this->rules as $name => $validation) {
                    $rules[$name] = $validation;
                }
            });
    }

    /**
     * Setup the form.
     *
     * @param  \Illuminate\Support\Fluent  $model
     * @param  \Orchestra\Contracts\Html\Form\Builder  $form
     *
     * @return void
     */
    abstract public function form(Fluent $model, FormBuilder $form);
}
