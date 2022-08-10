<?php


namespace RZP\Notifications;

use App;

use RZP\Constants\Mode;
use Illuminate\Foundation\Application;
use Razorpay\Trace\Logger as Trace;

abstract class BaseNotificationService
{
    protected $args;

    protected $event;

    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

    /**
     * Trace instance used for tracing
     *
     * @var Trace
     */
    protected $trace;

    protected $mode;

    /**
     * @var array
     */
    protected $files;

    public function __construct(string $event, array $args, $files=null)
    {
        $this->args  = $args;
        $this->app   = App::getFacadeRoot();
        $this->event = $event;
        $this->files = $files;

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }
        else
        {
            $this->mode = Mode::LIVE;
        }

        $this->trace = $this->app['trace'];
    }

    protected abstract function send();
}
