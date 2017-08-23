<?php

namespace RZP\Models\Base;

use App;
use Illuminate\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Constants\Mode;

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
    public function getInternalUsernameOrEmail(): string
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        return $dashboardInfo['admin_username'] ?? $dashboardInfo['user_email'] ?? 'DASHBOARD_INTERNAL';
    }

    /**
     * Execute a callable within a transaction.
     *
     * @param callable $callback
     * @param array    $params
     *
     * @return mixed
     */
    public function transaction($callback, ...$params)
    {
        if (is_array($callback) === true)
        {
            //
            // It's trying to call a function within the class.
            // If that function is protected/private, then Repo
            // won't be able to call it directly.
            // Wrapping it in a closure resolves the situation.
            //
            $closure = $this->closure($callback[1], ...$params);
            $callback = $closure;
        }

        return $this->repo->transaction($callback, $params);
    }

    /**
     * Provides a way to pass class private method with parameters
     * directly wherever closure is required.
     *
     * @param string $func
     * @param array  $params
     *
     * @return \Closure
     */
    public function closure(string $func, ...$params)
    {
        return function() use ($func, $params)
        {
            return $this->$func(...$params);
        };
    }

    protected function isTestMode(): bool
    {
        return ($this->mode === Mode::TEST);
    }

    protected function isLiveMode(): bool
    {
        return ($this->mode === Mode::LIVE);
    }
}
