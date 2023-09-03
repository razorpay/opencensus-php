<?php

namespace RZP\Models\Base;

use App;
use RZP\Base;
use RZP\Models\User;
use RZP\Models\Merchant;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use Razorpay\Trace\Logger as Trace;

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
     * @var User\Entity
     */
    protected $user;

    /**
     * Repository manager instance
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var Trace
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

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->auth = $this->app['basicauth'];

        $this->merchant = $this->auth->getMerchant();

        $this->user = $this->auth->getUser();

        $this->slack = $this->app['slack'];
    }

    public static function getNewInstance()
    {
        return new static;
    }

    public function core(): Core
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

    /**
     * @return \RZP\Models\Admin\Admin\Entity|
     *         \RZP\Models\User\Entity
     */
    public function getAuthAdminElseUser(): PublicEntity
    {
        return $this->auth->getAdmin() ?: $this->auth->getUser();
    }
}
