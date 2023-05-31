<?php

namespace RZP\Models\Base;

use App;
use Razorpay\Trace\Logger as Trace;
use Illuminate\Foundation\Application;

use RZP\Constants\Mode;
use RZP\Models\Merchant;
use RZP\Constants\Environment;
use RZP\Base\RepositoryManager;

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

    /**
     * @var Merchant\Entity
     */
    protected $merchant;

    /**
     * @var \Config
     */
    protected $config;

    /**
     * Secret for encryption/decryption of files
     *
     * @var secret
     */
    protected $secret;

    protected $cache;


    public function __construct()
    {
        $this->app = App::getFacadeRoot();

        if (isset($this->app['rzp.mode']))
        {
            $this->mode = $this->app['rzp.mode'];
        }

        $this->env = $this->app['env'];

        $this->trace = $this->app['trace'];

        $this->config = $this->app['config'];

        $this->repo = $this->app['repo'];

        $this->merchant = $this->app['basicauth']->getMerchant();

        $this->device = $this->app['basicauth']->getDevice();

        $batchApplication = $this->config->get('applications.batch');

        $this->secret = $batchApplication['aes_key'];

        $this->cache = $this->app['cache'];

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

        return $dashboardInfo['admin_username'] ?? $dashboardInfo['user_email'] ?? Merchant\Constants::DASHBOARD_INTERNAL;
    }

    /**
     * Returns only Admin's username
     * This method is primary used to get an identifier for user to construct a
     * slack message.
     *
     * Done for security purpose - https://razorpay.slack.com/archives/C3UAR8DQE/p1613118589167900
     */
    public function getAdminUsername(): string
    {
        $dashboardInfo = $this->app['basicauth']->getDashboardHeaders();

        return $dashboardInfo['admin_username'] ?? Merchant\Constants::DASHBOARD_INTERNAL;
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
     * Changes the mode and the database connection to the param passed
     *
     * @param string $mode
     */
    public function setModeAndDefaultConnection(string $mode = Mode::TEST)
    {
        //
        // This function updates the mode and app['rzp.mode'] properties
        // of the BasicAuth class that has been initialized.
        //
        $this->app['basicauth']->setModeAndDbConnection($mode);

        $this->mode = $mode;
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

    protected function isEnvironmentProduction(): bool
    {
        return ($this->env === Environment::PRODUCTION);
    }

    public function savePGOSDataToAPI(array $data)
    {

    }

    protected function getActorDetails()
    {
        $userId = null;
        $userEmail = null;
        $userType = 'user';
        if ($this->app['basicauth']->isAdminAuth() === true)
        {
            $userId = $this->app['basicauth']->getAdmin()->getId();
            $userEmail = $this->app['basicauth']->getAdmin()->getEmail();
            $userType = 'admin';
        }
        elseif (empty($this->app['basicauth']->getUser()) === false)
        {
            $userId = $this->app['basicauth']->getUser()->getId();
            $userEmail = $this->app['basicauth']->getUser()->getEmail();
        }
        elseif (empty($this->app['basicauth']->getMerchant()) === false)
        {
            $userId = $this->app['basicauth']->getMerchant()->getId();
            $userEmail = $this->app['basicauth']->getMerchant()->getEmail();
            $userType = 'merchant';
        }

        return [
            'actor_id'      => $userId ?? '100000Razorpay',
            'actor_email'   => $userEmail ?? 'default@razorpay.in',
            'actor_type'    => $userType,
        ];
    }

    protected function shouldBVTRequestHitRouter(?string $rzpTestCaseID): bool
    {
        if (empty($rzpTestCaseID) === true)
        {
            return false;
        }

        return ((app()->isEnvironmentQA() === true) and (str_contains($rzpTestCaseID,'_via_router') === true));
    }
}
