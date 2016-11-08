<?php

namespace RZP\Http\BasicAuth;

use ApiResponse;
use Config;
use Crypt;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Trace\TraceCode;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Models\Merchant;

class BasicAuth
{
    /*
     * Basic Auth currently goes as follows:
     *
     * Public -
     * rzp_mode_keyId:
     *
     * Private -
     * rzp_mode_keyId:merchant_secret
     *
     * Application -
     * rzp_mode:app_secret
     *
     * Application proxy -
     * rzp_mode_merchantId:app_secret
     *
     * Admin Auth
     * rzp_mode_admin:auth_token
     *
     */

    const HMAC_ALGO = 'sha256';

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * Key and secret sent by client for
     * basic auth.
     * @var array
     */
    private $creds = array(
        'key' => '',
        'public_key' => '',
        'secret' => '');

    /**
     * Key used for authentication
     * @var Key\Entity
     */
    private $key = null;

    /**
     * Merchant who is being authenticated
     * either by himself or by an internal
     * application
     *
     * @var Merchant\Entity
     */
    private $merchant = null;

    /**
     * Admin who is authenticating himself
     * through adminAuth
     */
    private $isAdmin = null;

    /**
     * During app authentication, the app
     * which has been authenticated.
     *
     * @var string
     */
    private $internalApp = null;

    /**
     * Authentication mode - test, live
     * @var string
     */
    private $mode;

    /**
     * Authentication type - private, public, internal
     * @var string
     */
    private $type;

    /**
     * Whether an internal app is doing an authentication
     * proxy to perform some action on merchant's
     * behalf
     * @var boolean
     */
    private $proxy = false;

    /**
     * Whether an internal app is doing an authentication
     * @var boolean
     */
    private $appAuth = false;

    /**
     * Denotes whether authentication happens over query params.
     * This is only allowed for public routes.
     * @var boolean
     */
    private $viaQueryParams = false;

    /**
     * Laravel request class instance
     * @var Request
     */
    protected $request;

    /**
     * Array of configurations of internal applications
     * @var array
     */
    protected $internalAppConfigs;

    /**
     * Trace instance used for tracing
     * @var Trace\Trace
     */
    protected $trace;

    /**
     * Api Route instance
     *
     * @var RZP\Http\Route
     */
    protected $route;

    /**
     * Array of dashboard headers
     * @var array
     */
    protected $dashboardHeaders = array();

    /**
     * Contains valid lengths of key.
     * rzp_mode - 3 + 1 + 4
     * 3 + 1 + 4 + 1 + 24
     * 3 + 1 + 4 + 1 + 14
     * 3 + 1 + 4 + 1 + 5 (rzp_$mode_admin)
     * @var array
     */
    protected static $validKeyLengths = [
        8, 23, 33, 14
    ];

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function init()
    {
        $app = $this->app;

        $this->request = $app['request'];
        $this->internalAppConfigs = $app['config']->get('applications');
        $this->cloud = $app['config']->get('app.cloud');
        $this->router = $app['router'];
        $this->trace = $this->app['trace'];
        $this->repo = $this->app['repo'];
        $this->route = $this->app['api.route'];
    }

    public function setCredentials()
    {
        $key = $this->request->getUser();

        $secret = $this->request->getPassword();

        if (($key === null) and
            ($secret === null))
        {
            return ApiResponse::httpAuthExpected();
        }

        $this->creds['secret'] = $secret;
        $this->creds['public_key'] = $key;

        return $this->checkAndSetKeyId($key);
    }

    public function checkAndSetKeyId($key)
    {
        if (($this->verifyKeyLength($key) === false) or
            ($this->verifyKeyPrefix($key) === false) or
            ($this->verifyAndSetMode($key) === false))
        {
            return $this->invalidApiKey();
        }

        $keyId = substr($key, 9);

        if ($keyId === false)
        {
            $this->creds['key'] = '';
            return;
        }

        $this->creds['key'] = $keyId;
    }

// --------------------- Basic Auths -------------------------------------------

    public function privateAuth()
    {
        $this->setType(Type::PRIVATE_AUTH);

        $res = $this->setCredentials();

        if ($res !== null)
        {
            return $res;
        }

        if ($this->verifyKeyExistence() === true)
        {
            $response = $this->verifySecret();

            if ($response === true)
            {
                return;
            }

            return $response;
        }
        else if ($this->verifyInternalAppAsProxy() === true)
        {
            $this->setProxyTrue();

            return;
        }

        return $this->invalidApiKey();
    }

    public function adminAuth()
    {
        $this->settype(Type::ADMIN_AUTH);

        $res = $this->setCredentials();

        // null is the good value here
        if ($res !== null)
        {
            return $res;
        }

        if ($this->getKey() !== 'admin')
        {
            return $res;
        }

        $this->setAdminTrue();

        $token = $this->getSecret();

        $admin = $this->fetchAdminOfToken($token);

        if ($admin)
        {
            return;
        }

        return $this->invalidApiKey();
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $key = $this->request->input('key_id');

        if ($key === null)
        {
            $res = $this->setCredentials();

            if ($res !== null)
                return $res;
        }
        else
        {
            $res = $this->setKeyFromQueryParams();

            if ($res !== null)
                return $res;
        }

        if ($this->verifyKeyExistence() !== true)
        {
            return $this->invalidApiKey();
        }

        if (($this->getSecret() !== '') and
            ($this->getSecret() !== null))
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE);
        }

        $this->fetchMerchantOfKey($this->key);
    }

    public function directAuth()
    {
        $key = $this->request->input('key_id');

        if (empty($key) === false)
        {
            return $this->publicAuth();
        }

        $this->setType(Type::DIRECT_AUTH);
    }

    public function appAuth()
    {
        $this->setType(Type::PRIVILEGE_AUTH);

        $this->setAppTrue();

        $res = $this->setCredentials();

        if ($res !== null)
        {
            return $res;
        }

        // Check key is blank and it's an internal app
        if (($this->isKeyBlank()) and
            ($this->verifyInternalApp()))
        {
            // It's an internal auth. We check whether dashboard
            // merchant header is set. In that case, it's coming
            // from merchant dashboard and not admin dashboard
            // which can potentially cause a security issue and
            // hence needs to be actively checked against.

            $this->checkForDashboardMerchantHeader();

            $this->setDashboardHeaders();

            return;
        }

        // Say invalid route for whenever
        // appAuth authentication fails
        return ApiResponse::routeNotFound();
    }

    public function proxyAuth()
    {
        $this->setType(Type::PRIVATE_AUTH);

        $this->proxy = true;

        $res = $this->setCredentials();

        if ($res !== null)
        {
            return $res;
        }

        // The internal app is authenticated as a merchant
        // and allowed to do ops on merchant's behalf
        if ($this->verifyInternalAppAsProxy() === true)
        {
            $this->setDashboardHeaders();

            return;
        }

        return ApiResponse::routeNotFound();
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicCallbackAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $key = $this->router->current()->getParameter('key');

        if ($key === null)
        {
            $res = $this->setCredentials();

            if ($res !== null)
                return $res;
        }

        $this->creds['secret'] = null;
        $this->creds['public_key'] = $key;

        // If key is wrong in formatting or something, send error back
        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->invalidApiKey();
        }

        if ($this->verifyKeyExistence() !== true)
        {
            return $this->invalidApiKey();
        }

        if (($this->getSecret() !== '') and
            ($this->getSecret() !== null))
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE);
        }

        $this->fetchMerchantOfKey($this->key);
    }

    public function feature()
    {
        return $this->verifyFeatureAccess();
    }

// --------------------- Basic Auths Ends --------------------------------------

// --------------------- Verifiers ---------------------------------------------

    /**
     * Checks if the accessed route is a beta feature route, if yes
     * checks if the merchant has access to the feature
     */
    public function verifyFeatureAccess()
    {
        $route = $this->getCurrentRouteName();

        if ($this->route->isCurrentRouteInFeatureMap() === true)
        {
            //
            // Current route is in feature map list.
            //

            $accessedFeature = Route::$routeNameToFeatureMap[$route];

            if ($this->merchant->isFeatureEnabled($accessedFeature))
            {
                return null;
            }

            return ApiResponse::routeNotFound();
        }

        return null;
    }

    protected function verifyKeyLength($key)
    {
        $keyLen = strlen($key);

        return in_array($keyLen, static::$validKeyLengths);
    }

    protected function verifyKeyPrefix($key)
    {
        return (substr($key, 0, 4) === 'rzp_');
    }

    protected function verifyAndSetMode($key)
    {
        $mode = substr($key, 4, 4);

        if ($mode === Mode::LIVE)
        {
            $this->setMode(Mode::LIVE);
        }
        else if ($mode === Mode::TEST)
        {
            $this->setMode(Mode::TEST);
        }
        else
        {
            return false;
        }

        if ((strlen($key) > 8) and
            (substr($key, 8, 1) !== '_'))
        {
            return false;
        }

        \Database\DefaultConnection::set($mode);

        return true;
    }

    /**
     * Verify key exists by fetching it
     * @return boolean
     */
    protected function verifyKeyExistence()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
            return;

        //
        // For keys sent by merchants, make sure they exist in db.
        //
        $key = $this->fetchKey($keyId);

        return ($key !== null);
    }

    /**
     * Used for private/secret authentication.
     * These requests are expected to originate
     * from merchant's server
     *
     * @param  string   $keyId
     * @param  string   $keySecret
     * @return boolean/Response
     */
    protected function verifySecret()
    {
        $keyEntity = $this->key;

        $secret = $this->getSecret();

        if ($secret === '')
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, ['key_id' => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        if (Crypt::decrypt($keyEntity->getSecret()) !== $secret)
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_INVALID_API_SECRET, ['key_id' => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }

        $this->fetchMerchantOfKey($keyEntity);

        return true;
    }

    /**
     * Matches the secret provided against the list of
     * applications secrets with us. If any matches, then
     * that  particular app is allowed to continue
     * it's operation.
     *
     * If a merchant id is provided, then the app is
     * authenticating as that merchant and trying to
     * perform operations related to that merchant.
     *
     * Used if private authentication fails
     *
     * @return boolean
     */
    protected function verifyInternalAppAsProxy()
    {
        if ($this->verifyInternalApp() === false)
        {
            return false;
        }

        // The key in case of app proxy will be the merchant id
        $merchantId = $this->getKey();

        $this->merchant = $this->repo->merchant->find($merchantId);

        // If merchant id isn't found, then return false.
        return ($this->merchant !== null);
    }

    /**
     * Verify the request is made by an app (internal/external)
     * @return boolean
     */
    protected function verifyInternalApp()
    {
        // First, check that the secret matches one of
        // the application's secrets
        if ($this->verifyInternalAppSecret() === false)
        {
            return false;
        }

        $appRoutes = Route::$internalApps[$this->internalApp];

        // Now that the secret matches, check whether the current
        // route is allowed for this particular app.

        // If '*' is present in the app's routes, then all routes
        // are allowed
        if (in_array('*', $appRoutes))
        {
            return true;
        }

        if (in_array($this->getCurrentRouteName(), $appRoutes, true) === false)
        {
            return false;
        }

        return true;
    }

    /**
     * Verifies that the request is coming from an internal ip
     * In this case, it's amazon's internal ip
     * in the 10.0.*.* range
     * @return boolean
     */
    protected function verifyClientIpInternal()
    {
        // Only if the application is deployed in cloud,
        // then verify internal ip
        if ($this->cloud === false)
            return true;

        // Check request is from internal ip
        $clientIp = $this->request->getClientIp();

        $clientIpRegex = '/^10\.0\.[0-9]{1,3}\.[0-9]{1,3}$/';

        return preg_match($clientIpRegex, $clientIp);
    }

    protected function checkForDashboardMerchantHeader()
    {
        $dash = $this->request->headers->get('X-Dashboard-Merchant');

        if (empty($dash) === false)
        {
            $this->trace->warning(
                TraceCode::DASHBOARD_MERCHANT_APP_AUTH_UNEXPECTED);
        }
    }

    protected function setDashboardHeaders()
    {
        $headers = $this->request->headers;

        $this->dashboardHeaders = array(
            'dashboard'     => $headers->get('X-Dashboard'),
            'merchant'      => $headers->get('X-Dashboard-Merchant'),
            'admin_user'    => $headers->get('X-Dashboard-Username')
        );
    }

    public function getDashboardHeaders()
    {
        return $this->dashboardHeaders;
    }

    protected function verifyInternalAppSecret()
    {
        $secret = $this->getSecret();

        $internalApps = $this->internalAppConfigs;

        $verify = false;

        foreach ($internalApps as $name => $info)
        {
            if ((isset($info['secret'])) and
                ($info['secret'] === $secret))
            {
                $verify = true;

                $this->internalApp = $name;

                if ((isset($info['cloud'])) and
                    ($info['cloud'] === true))
                {
                    // Disable internal ip checks for now
                    // $verify = $this->verifyClientIpInternal();
                }

                break;
            }
        }

        return $verify;
    }

// --------------------- Verifiers Ends ----------------------------------------

// --------------------- Getters -----------------------------------------------

    protected function getKey()
    {
        return $this->creds['key'];
    }

    private function getSecret()
    {
        return $this->creds['secret'];
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function getMerchantId()
    {
        return $this->merchant->getKey();
    }

    public function getMerchantIdOfKey()
    {
        if ($this->key === null)
        {
            return null;
        }

        return $this->key->getMerchantId();
    }

    public function getPublicKey()
    {
        return $this->creds['public_key'];
    }

    protected function getCurrentRouteName()
    {
        return $this->app['api.route']->getCurrentRouteName();
    }

    public function getAuthType()
    {
        return $this->type;
    }

    public function isCron()
    {
        $cron = ($this->internalApp === 'cron');

        return $cron;
    }

// --------------------- Getters Ends ------------------------------------------

// --------------------- Setters -----------------------------------------------

    public function setMode($mode)
    {
        $this->mode = $mode;
        $this->app['rzp.mode'] = $mode;
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    protected function setAdminTrue()
    {
        $this->isAdmin = true;
    }

    protected function setProxyTrue()
    {
        $this->proxy = true;

        $this->setAppTrue();
    }

    protected function setAppTrue()
    {
        $this->appAuth = true;
    }

// --------------------- Setters Ends ------------------------------------------

    public function isProxyAuth()
    {
        return $this->proxy;
    }

    public function isAdminAuth()
    {
        return $this->isAdmin;
    }

    public function isAppAuth()
    {
        return $this->appAuth;
    }

    public function isPublicAuth()
    {
        return ($this->type === Type::PUBLIC_AUTH);
    }

    public function isPrivateAuth()
    {
        return ($this->type === Type::PRIVATE_AUTH);
    }

    public function isPrivilegeAuth()
    {
        return ($this->type === Type::PRIVILEGE_AUTH);
    }

    public function isProxyOrPrivilegeAuth()
    {
        return (($this->isProxyAuth()) or ($this->isPrivilegeAuth()));
    }

    protected function setKeyFromQueryParams()
    {
        // Get key from input params
        $key = $this->request->input('key_id');

        // If not provided, then send error asking for it
        if (($key === null) or
            ($key === ''))
        {
            return ApiResponse::provideApiKey();
        }

        $this->viaQueryParams = true;

        $this->creds['secret'] = null;
        $this->creds['public_key'] = $key;

        // Remove 'key_id' from query params
        $this->request->query->remove('key_id');
        $this->request->request->remove('key_id');

        // If key is wrong in formatting or something, send error back
        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->invalidApiKey();
        }
    }

    protected function fetchKey($keyId)
    {
        $this->key = $this->repo->key->findNotExpired($keyId);

        return $this->key;
    }

    protected function fetchMerchantOfKey($key)
    {
        $merchantId = $key->getMerchantId();

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->checkMerchantActivatedForLive();

        return $this->merchant;
    }

    protected function fetchAdminOfToken($token)
    {
        $this->admin = $this->repo->admin_token->findValidToken($token)->admin;

        return $this->admin;
    }

    protected function checkMerchantActivatedForLive()
    {
        $mode = $this->getMode();

        if ($mode === Mode::TEST)
        {
            return;
        }

        if ($this->merchant->isActivated() === false)
        {
            throw new Exception\LogicException(
                'Must not be able to make live request when not activated');
        }
    }

    protected function invalidApiKey()
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_API_KEY, ['key_id' => $this->getKey()]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }

    protected function isKeyBlank()
    {
        return ($this->getKey() === '');
    }

    public function sign($str)
    {
        if ($this->key === null)
        {
            throw new Exception\LogicException('Key cannot be null here');
        }

        $secret = Crypt::decrypt($this->key->getSecret());

        return hash_hmac(self::HMAC_ALGO, $str, $secret);
    }
}
