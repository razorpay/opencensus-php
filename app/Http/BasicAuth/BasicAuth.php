<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use Config;
use ApiResponse;

use Illuminate\Routing\Router;
use RZP\Base\RepositoryManager;
use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Models\Device;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;

/**
 * Class BasicAuth
 *
 *
 * Basic Auth currently goes as follows:
 *
 * Public -
 * rzp_mode_keyId:
 *
 * Private -
 * rzp_mode_keyId:merchant_secret
 *
 * Application/Internal -
 * rzp_mode:app_secret
 *
 * Application proxy -
 * rzp_mode_merchantId:app_secret
 *
 * Device -
 * rzp_mode_keyId:device_token
 *
 * Admin Auth
 * rzp_mode_admin:auth_token
 *
 * @package RZP\Http\BasicAuth
 */
class BasicAuth
{
    const HMAC_ALGO = 'sha256';

    /**
     * To support Account Auth: Allows API requests to be served under the
     * scope of a merchant ID that is sent as the value to this header
     *
     * On Privilege auth                - set to any merchant ID
     * On admin auth                    - set to any merchant under the current org
     * For private auth (marketplace)   - set to any linked account under the merchant
     */
    const ACCOUNT_HEADER_KEY = 'X-Razorpay-Account';

    /**
     * Dashboard headers are prefixed with following literal.
     */
    const DASHBOARD_HEADER_PREFIX = 'x-dashboard';

    const ADMIN_TOKEN_HEADER = 'X-Admin-Token';

    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * OAuth's registered client id.
     *
     * @var string
     */
    protected $oauthClientId;

    /**
     * OAuth's access token (public) id.
     *
     * @var string
     */
    protected $accessTokenId;

    /**
     * Key and secret sent by client for
     * basic auth.
     *
     * account_id   -> value passed in the ACCOUNT_HEADER_KEY, for account auth
     *
     * @var array
     */
    private $creds = [
        'key'           => '',
        'public_key'    => '',
        'secret'        => '',
        'account_id'    => '',
    ];

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
    private $isAdmin = false;

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
     * Device being used in device auth routes
     *
     * @var Device\Entity
     */
    private $device = null;

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
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    /**
     * Api Route instance
     *
     * @var \RZP\Http\Route
     */
    protected $route;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * @var bool
     */
    protected $cloud;

    /**
     * Array of dashboard headers
     * @var array
     */
    protected $dashboardHeaders = array();

    /**
     * Contains valid lengths of key.
     * rzp_mode            = 3 + 1 + 4
     * rzp_mode_admin      = 3 + 1 + 4 + 1 + 5
     * rzp_mode_keyId      = 3 + 1 + 4 + 1 + 24
     * rzp_mode_merchantId = 3 + 1 + 4 + 1 + 14
     *
     * NOTE: key length 29 is used for OAuth public tokens,
     * hence DO NOT add 29 as a valid length for basicAuth
     *
     * @var array
     */
    protected static $validKeyLengths = [
        8, 14, 23, 33
    ];

    protected $adminOrgId = null;

    protected $orgId      = null;

    protected $orgHostName = null;

    public function __construct($app)
    {
        $this->app = $app;
    }

    public function init()
    {
        $app = $this->app;

        $this->request            = $app['request'];
        $this->internalAppConfigs = $app['config']->get('applications');
        $this->cloud              = $app['config']->get('app.cloud');
        $this->router             = $app['router'];
        $this->trace              = $this->app['trace'];
        $this->repo               = $this->app['repo'];
        $this->route              = $this->app['api.route'];
        $this->merchant           = null;
        $this->device             = null;
        $this->isAdmin            = false;
        $this->appAuth            = false;
        $this->proxy              = false;
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

        $keyError = $this->checkAndSetKeyId($key);

        if ($keyError !== null)
        {
            return $keyError;
        }

        // Fetch ID sent in the account auth header
        $accountId = $this->request->headers->get(self::ACCOUNT_HEADER_KEY);

        return $this->checkAndSetAccountId($accountId);
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

    /**
     * If Account ID was sent, verify and set its value in $this->creds[]
     *
     * @param  string|null      $accountId
     * @return ApiResponse|null
     */
    protected function checkAndSetAccountId($accountId)
    {
        if ($accountId === null)
        {
            $this->creds['account_id'] = '';

            return null;
        }

        if ($this->verifyAccountId($accountId) === false)
        {
            return $this->invalidAccountId($accountId);
        }

        $this->creds['account_id'] = $accountId;

        return null;
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

        if ($this->isKeyExisting() === true)
        {
            $response = $this->verifyKeyNotExpired();

            if ($response !== true)
            {
                return $response;
            }

            $response = $this->verifySecret();

            if ($response === true)
            {
                return $this->checkAndSetAccountScope();
            }

            return $response;
        }
        else if ($this->verifyInternalAppAsProxy() === true)
        {
            $this->setDashboardHeaders();

            $this->setProxyTrue();

            $this->setAdminAuthIfApplicable();

            return $this->checkAndSetAccountScope();
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
            {
                return $res;
            }
        }
        else
        {
            $res = $this->setKeyFromQueryParams();

            if ($res !== null)
            {
                return $res;
            }
        }

        $response = $this->verifyKeyExistence();

        if ($response !== true)
        {
            return $response;
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
            $response = $this->setAdminAuthIfApplicable();

            if ($response !== null)
            {
                return $response;
            }

            $this->checkForDashboardMerchantHeader();

            $this->setDashboardHeaders();

            return $this->checkAndSetAccountScope();
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
            $response = $this->setAdminAuthIfApplicable();

            if ($response !== null)
            {
                return $response;
            }

            $this->setDashboardHeaders();

            return $this->checkAndSetAccountScope();
        }

        return ApiResponse::routeNotFound();
    }

    /**
     * The return values will be those of:
     *
     * @return \Response|null
     */
    protected function setAdminAuthIfApplicable()
    {
        $adminToken = $this->request->header(self::ADMIN_TOKEN_HEADER);

        if ($adminToken !== null)
        {
            // Remove the token so that subsequent code has no
            // access to it (prevents logging, etc.)
            $this->request->headers->remove(self::ADMIN_TOKEN_HEADER);

            $token = $this->fetchAdminToken($adminToken);

            if ($token->getAdminId() !== null)
            {
                $this->setAdminTrue();

                $this->admin = $token->admin;

                $this->adminOrgId = $this->admin->getOrgId();

                return;
            }

            return $this->invalidApiKey();
        }

        // `Route::$admin` contains routes that should strictly
        // be on admin auth and cannot be accessed over others
        // (proxy, internal, etc.)
        $currentRoute = $this->route->getCurrentRouteName();

        if (in_array($currentRoute, Route::$admin, true) === true)
        {
            return $this->invalidApiKey();
        }
    }

    public function deviceAuth()
    {
        $this->setType(Type::DEVICE_AUTH);

        $res = $this->setCredentials();

        if ($res !== null)
        {
            return $res;
        }

        $response = $this->verifyKeyExistence();

        if ($response !== true)
        {
            return $response;
        }

        $this->fetchMerchantOfKey($this->key);

        $response = $this->verifyDeviceToken();

        if ($response === true)
        {
            return;
        }

        return $response;
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicCallbackAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $key = $this->router->current()->parameter('key');

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

        $response = $this->verifyKeyNotExpired();

        if ($response !== true)
        {
            return $response;
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
     * Checks if the accessed route is a feature route, if yes
     * checks if the merchant has access to the feature
     */
    public function verifyFeatureAccess()
    {
        $currentRoute = $this->route->getCurrentRouteName();

        //
        // A route can belong to multiple features
        // This fetches an array of all features mapped to the route
        //
        // TODO: Fix this! BA calls Route and Route calls BA. Not a good design.
        //
        $features = Route::getFeaturesForRoute($currentRoute);

        if (empty($features) === true)
        {
            return null;
        }

        //
        // If the merchant has at least one of the features
        // in the $features array enabled, we allow the request
        //
        $merchantFeatures = $this->merchant->getEnabledFeatures();

        $commonFeatures = array_intersect($merchantFeatures, $features);

        if (empty($commonFeatures) === false)
        {
            return null;
        }

        return ApiResponse::routeNotFound();
    }

    protected function verifyAccountId(string & $accountId)
    {
        try
        {
            Merchant\AccountEntity::verifyIdAndSilentlyStripSign($accountId);
        }
        catch (\Exception $e)
        {
            return false;
        }

        return true;
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
        if ($this->isKeyExisting() === false)
        {
            return $this->invalidApiKey();
        }

        return $this->verifyKeyNotExpired();
    }

    protected function isKeyExisting()
    {
        $keyId = $this->getKey();

        if ($keyId === '')
        {
            return false;
        }

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
     * @return bool|Response
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

    protected function verifyKeyNotExpired()
    {
        if ($this->key->isExpired() === true)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_API_KEY_EXPIRED);
        }

        return true;
    }

    /**
     * Used for device verification. Checks
     * that the device exists and belongs
     * to the calling merchant
     * @return boolean/Response
     */
    protected function verifyDeviceToken()
    {
        $keyEntity = $this->key;

        $deviceToken = $this->getSecret();

        if ($deviceToken === '')
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, ['key_id' => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $device = $this->repo->device->findByAuthToken($deviceToken);

        $this->device = $device;

        if (($device === null) or
            ($keyEntity->merchant->getId() !== $device->merchant->getId()))
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_INVALID_API_SECRET, ['key_id' => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }
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

        $merchant = $this->repo->merchant->find($merchantId);

        $this->setMerchant($merchant);

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

        if (in_array($this->route->getCurrentRouteName(), $appRoutes, true) === false)
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
        {
            return true;
        }

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

        $this->dashboardHeaders =[
            // String 'true' or null
            'dashboard' => $headers->get('X-Dashboard'),
        ];

        // Gets all headers with 'X-Dashboard' as prefix and assign them to a
        // snake cased key (with prefix removed) in $this->dashboardHeaders.

        $dashHeaderPrefixLen = strlen(self::DASHBOARD_HEADER_PREFIX) + 1;

        $dashHeadersKeys = array_filter(
                                $headers->keys(),
                                function ($k)
                                {
                                    return ($k !== self::DASHBOARD_HEADER_PREFIX) and
                                        (starts_with($k, self::DASHBOARD_HEADER_PREFIX));
                                });

        foreach ($dashHeadersKeys as $dashHeadersKey)
        {
            // Gets key for $this->dashboardHeaders, which is snake_cased header
            // with prefix removed.
            $key = substr($dashHeadersKey, $dashHeaderPrefixLen);
            $key = snake_case(camel_case($key));

            $this->dashboardHeaders[$key] = $headers->get($dashHeadersKey);
        }
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

                // if ((isset($info['cloud'])) and
                //     ($info['cloud'] === true))
                // {
                //     Disable internal ip checks for now
                //     $verify = $this->verifyClientIpInternal();
                // }

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

    protected function getAccountId()
    {
        return $this->creds['account_id'];
    }

    public function getMode()
    {
        return $this->mode;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }

    public function getDevice()
    {
        return $this->device;
    }

    public function getAdminToken()
    {
        return $this->adminToken;
    }

    public function getAdmin()
    {
        return $this->admin;
    }

    public function getAdminOrgId()
    {
        return $this->adminOrgId;
    }

    public function getMerchantId()
    {
        if ($this->getMerchant() === null)
        {
            return null;
        }

        return $this->merchant->getKey();
    }

    public function getAccessTokenId()
    {
        return $this->accessTokenId;
    }

    public function getOAuthClientId()
    {
        return $this->oauthClientId;
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

    public function getAuthType()
    {
        return $this->type;
    }

    public function getInternalApp()
    {
        return $this->internalApp;
    }

    public function isDashboardApp()
    {
        return ($this->internalApp === 'dashboard');
    }

    public function isCron()
    {
        $cron = ($this->internalApp === 'cron');

        return $cron;
    }

// --------------------- Getters Ends ------------------------------------------

// --------------------- Setters -----------------------------------------------

    public function setMode(string $mode)
    {
        $this->mode = $mode;
        $this->app['rzp.mode'] = $mode;
    }

    public function setModeAndDbConnection(string $mode)
    {
        $this->setMode($mode);

        \Database\DefaultConnection::set($mode);
    }

    /**
     * Sets $merchant instance var value by given $merchantId.
     * Called by OAuth flow. OAuth server response contains the same($merchantId).
     *
     * @param string $merchantId
     */
    public function setMerchantById(string $merchantId)
    {
        $merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->merchant = $merchant;
    }

    public function setAccessTokenId(string $tokenId)
    {
        $this->accessTokenId = $tokenId;
    }

    public function setOAuthClientId(string $oauthClientId)
    {
        $this->oauthClientId = $oauthClientId;
    }

    public function setMerchant($merchant)
    {
        $this->merchant = $merchant;
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    protected function setAdminTrue()
    {
        $this->isAdmin = true;
    }

    public function setPublicKey(string $publicKey)
    {
        $this->creds['public_key'] = $publicKey;
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

    public function isAppAuth()
    {
        return $this->appAuth;
    }

    public function isAdminAuth()
    {
        return $this->isAdmin;
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

    public function isDeviceAuth()
    {
        return ($this->type === Type::DEVICE_AUTH);
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
        $this->key = $this->repo->key->find($keyId);

        return $this->key;
    }

    protected function fetchMerchantOfKey($key)
    {
        $merchantId = $key->getMerchantId();

        $this->merchant = $this->repo->merchant->findOrFail($merchantId);

        $this->checkMerchantActivatedForLive();

        return $this->merchant;
    }

    protected function fetchAdminToken($token)
    {
        $mode = $this->getLiveConnection();

        // Admin token check should always be done in the
        // live mode (since we don't sync it in heimdall)
        $this->adminToken = $this->repo->admin_token->connection($mode)->findOrFailToken($token);

        return $this->adminToken;
    }

    /**
     * Fetch merchant by ID and sets it to $this->merchant
     * for the current request
     */
    protected function checkAndSetAccountScope()
    {
        if ($this->isAccountAuthAllowed() === false)
        {
            return;
        }

        $account = $this->repo
                        ->merchant
                        ->find($this->getAccountId());

        if (($account === null) or
            ($this->validateAccountForCurrentAuthType($account) === false))
        {
            return $this->invalidAccountId($this->getAccountId());
        }

        $this->merchant = $account;
    }

    /**
     * Check pre-conditions for setting account auth via
     * the `X-Razorpay-Account` header
     *
     * @return bool
     */
    protected function isAccountAuthAllowed() : bool
    {
        $authType = $this->getAuthType();

        if (($this->getAccountId() === '') or
            (empty($authType) === true))
        {
            return false;
        }

        if ($this->isPrivilegeAuth() === true)
        {
            return true;
        }

        // For Admin auth requests - $this->admin should be set
        if (($this->isAdminAuth() === true) and
            (empty($this->admin) === false))
        {
            return true;
        }

        // For Private auth requests - $this->merchant should be set
        if ($this->isPrivateAuth() === true)
        {
            if ((empty($this->merchant) === false) or
                (empty($this->getAccountId()) === false))
            {
                return true;
            }
        }

        return false;
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

    protected function invalidAccountId(string $accountId)
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_ACCOUNT_HEADER,
            [
                'auth_type'     => $this->getAuthType(),
                'key_id'        => $this->getKey(),
                'account_id'    => $accountId,
            ]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
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

    /**
     * Returns true if the account fetched can be set as merchant
     * for the current auth type
     *
     * @param $account
     *
     * @return bool
     */
    protected function validateAccountForCurrentAuthType(Merchant\Entity $account) : bool
    {
        $authType = $this->getAuthType();

        if (empty($this->admin) === false)
        {
            // returning true on admin auth because admin access middleware
            // checks and drops if it is not a valid merchant.

            return true;
        }

        switch ($authType)
        {
            case Type::PRIVATE_AUTH:
                return ($account->getParentId() === $this->getMerchant()->getId());

            case Type::PRIVILEGE_AUTH:
                return true;

            default:
                return false;
        }
    }

    public function validateSuperAdminAccess()
    {
        $admin = $this->getAdmin();

        if ($admin->isSuperAdmin() === false)
        {
            $data = ['admin_id' => $admin->getId()];

            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_SUPERADMIN_ACCESS_REQUIRED,
                null,
                $data);
        }
    }

    public function setOrgId($orgId)
    {
        $this->orgId = $orgId;
    }

    public function getOrgId()
    {
        return $this->orgId;
    }

    public function fetchOrgByHostname($orgHostname)
    {
        $mode = $this->getLiveConnection();

        // Org Hostname check should always be done in the
        // live mode (since we don't sync it in heimdall)
        $org = $this->repo->org->connection($mode)->findOrFailByHostname($orgHostname);

        return $org;
    }

    /**
     * Some tables are only synced in live, so this will give connection of the live db based on test env.
     *
     * @return string
     */
    public function getLiveConnection()
    {
        if ($this->app->environment('testing') === false)
        {
            $mode = Mode::LIVE;
        }
        else
        {
            $mode = Mode::TEST;
        }

        return $mode;
    }

    /**
     * Each org can have multiple hostnames
     * Keeping track of the hostname when request is received.
     *
     * @param $orgHostName
     *
     * @return $this
     */
    public function setOrgHostName($orgHostName)
    {
        $this->orgHostName = $orgHostName;

        return $this;
    }

    public function getOrgHostName()
    {
        return $this->orgHostName;
    }
}
