<?php namespace Orchestra\Extension;

use Illuminate\Http\Request;
use Illuminate\Session\SessionStore;

class Debugger implements Contracts\DebuggerInterface
{
    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Session Manager instance.
     *
     * @var \Illuminate\Session\SessionStore
     */
    protected $session;

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Http\Request            $request
     * @param  \Illuminate\Session\SessionStore    $session
     */
    public function __construct(Request $request, SessionStore $session)
    {
        $this->request = $request;
        $this->session = $session;
    }

    /**
     * Determine whether current request is in safe mode or not.
     *
     * @return boolean
     */
    public function check()
    {
        $input   = $this->request->input('safe_mode');
        $session = $this->session;

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
