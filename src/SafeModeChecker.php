<?php namespace Orchestra\Extension;

use Illuminate\Http\Request;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Contracts\Extension\SafeMode;
use Illuminate\Contracts\Config\Repository;

class SafeModeChecker implements SafeMode
{
    /**
     * Config instance.
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Mode status.
     *
     * @var string|null
     */
    protected $status;

    /**
     * Construct a new Application instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Http\Request  $request
     */
    public function __construct(Repository $config, Request $request)
    {
        $this->config = $config;
        $this->request = $request;
    }

    /**
     * Determine whether current request is in safe mode or not.
     *
     * @return bool
     */
    public function check()
    {
        if (is_null($this->status)) {
            $this->verifyStatus();
        }

        return ($this->status === 'safe');
    }

    /**
     * Verify safe mode status.
     *
     * @return void
     */
    protected function verifyStatus()
    {
        $config = $this->config->get('orchestra/extension::mode', 'normal');
        $input = $this->request->input('_mode', $config);

        if ($input == 'safe') {
            $this->enableSafeMode();
        } else {
            $this->disableSafeMode();
        }
    }

    /**
     * Disable safe mode.
     *
     * @return void
     */
    protected function disableSafeMode()
    {
        $this->config->set('orchestra/extension::mode', $this->status = 'normal');
    }

    /**
     * Enable safe mode.
     *
     * @return void
     */
    protected function enableSafeMode()
    {
        $this->config->set('orchestra/extension::mode', $this->status = 'safe');
    }
}
