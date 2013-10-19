<?php namespace Orchestra\Extension;

use Illuminate\Container\Container;

class Debugger implements Contracts\DebuggerInterface
{
    /**
     * Application instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app = null;

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Container\Container  $app
     */
    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
     * Determine whether current request is in safe mode or not.
     *
     * @return boolean
     */
    public function check()
    {
        $input   = $this->app['request']->input('safe_mode');
        $session = $this->app['session'];

        if ($input == 'off') {
            $session->forget('orchestra.safemode');
            return false;
        }

        $mode = $session->get('orchestra.safemode', 'off');

        if ($input === 'on' and $mode !== $input) {
            $session->put('orchestra.safemode', $mode = $input);
        }

        return ($mode === 'on');
    }
}
