<?php

namespace RZP\Models\Base;

use App;
use Illuminate\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Trace\Trace;

class Core
{
    /**
     * The application instance.
     *
     * @var Application
     */
    protected $app;

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
     * Test/Live mode
     *
     * @var string
     */
    protected $mode;

    /**
     * Environment - production/testing/beta
     *
     * @var String
     */
    protected $env;

    protected $merchant;

    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->device = $this->app['basicauth']->getDevice();

        $this->init();
    }

    /**
     * This function can be over-loaded by child classes to init
     * class specific instance properties. This will prevent class
     * constructor from being over-loaded every time.
     */
    protected function init()
    {
    }

    /**
     * Returns Admin's username or User's email, whichever is available from
     * dashboard headers. If both of them are not available returns literal
     * 'DASHBOARD_INTERNAL'.
     *
     * This method is primary used to get an identifier for user to construct a
     * slack message. Eg. something got edited/removed by $user.
     *
     * @return string
     */
    protected function getInternalUsernameOrEmail(): string
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        return $dashboardInfo['admin_username'] ?? $dashboardInfo['user_email'] ?? 'DASHBOARD_INTERNAL';
    }
}
