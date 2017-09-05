<?php

namespace RZP\Models\Base;

use App;

use RZP\Base;
use RZP\Models\Merchant;

class Service
{
    /**
     * The application instance.
     *
     * @var Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * BasicAuth entity
     * @var BasicAuth
     */
    protected $auth;

    /**
     * The merchant making the request.
     * If merchant isn't making the request, then
     * it's null
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * Repository manager instance
     * @var Base\RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var \RZP\Trace\Trace
     */
    protected $trace;

    /**
     * Slack Client instance
     * @var Razorpay\Slack\Facades\Slack
     */
    protected $slack;

    /**
     * Instance of 'core' class of the respective namespace entity.
     */
    protected $core;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->auth = $this->app['basicauth'];

        $this->slack = $this->app['slack'];
    }

    public static function getNewInstance()
    {
        return new static;
    }

    public function core()
    {
        if ($this->core !== null)
        {
            return $this->core;
        }

        $class = get_class($this);

        // Remove end '\Service' from class name.
        $class = substr($class, 0, -7) . 'Core';

        $this->core = new $class;

        return $this->core;
    }
}
