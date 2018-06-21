<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use Config;
use ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Razorpay\OAuth\Client as OAuthClient;
use Razorpay\OAuth\Application as OAuthApp;

use RZP\Exception;
use RZP\Http\Route;
use RZP\Models\Key;
use RZP\Models\Device;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Http\RequestHeader;
use RZP\Base\RepositoryManager;
use RZP\Models\User\Entity as User;

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
    const HMAC_ALGO               = 'sha256';

    /**
     * Dashboard headers are prefixed with following literal.
     */
    const DASHBOARD_HEADER_PREFIX = 'x-dashboard';

    const ADMIN_TOKEN_HEADER      = 'X-Admin-Token';

    /**
     * Callback key in the partner token flow looks like this:
     * rzp_test_1DP5mmOlF5G5ag~rzp_partner_ACIg2tb8NySnuh
     *
     * Delimiter used is defined in this const.
     */
    const PARTNER_CALLBACK_KEY_DELIMITER = '~';

    const PARTNER_TOKEN           = 'partner_token';
    const KEY                     = 'key';
    const KEY_ID                  = 'key_id';
    const ACCOUNT_ID              = 'account_id';
    const SECRET                  = 'secret';
    const PUBLIC_KEY              = 'public_key';
    const AUTH_TYPE               = 'auth_type';

    /**
     * Client types are interpreted differently in API vs
     * auth-service. We store the mapping here. API uses
     * test and live and restricts them the test/live modes
     * respectively. Auth-service refers to these as dev and
     * prod and the interpretation for Pure-platforms there
     * is not related to these modes from API.
     */
    protected static $clientModes = [
        'test' => 'dev',
        'live' => 'prod',
    ];
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * OAuth's registered client id.
     *
     * @var string|null
     */
    protected $oauthClientId;

    /**
     * OAuth application id
     *
     * @var string|null
     */
    protected $applicationId;

    /**
     * OAuth's access token (public) id.
     *
     * @var string|null
     */
    protected $accessTokenId;

    /**
     * @var string|null
     */
    protected $partnerMerchantId;

    /**
     * Key and secret sent by client for
     * basic auth.
     *
     * account_id   -> value passed in the ACCOUNT_HEADER_KEY, for account auth
     *
     * @var array
     */
    private $creds = [
        self::KEY           => '',
        self::PUBLIC_KEY    => '',
        self::SECRET        => '',
        self::ACCOUNT_ID    => '',
        self::PARTNER_TOKEN => '',
    ];

    /**
     * Key used for authentication
     * @var Key\Entity
     */
    private $key = null;

    /**
     * Used to identify partner flows
     * @var bool
     */
    private $isPartnerAuth = false;

    /**
     * @var AuthCreds
     */
    public $authCreds = null;

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

    protected $adminOrgId  = null;

    protected $adminToken;

    protected $orgId       = null;

    protected $orgHostName = null;

    /**
     * User is set from the id received in X-Dashboard-User-Id header.
     *
     * @var \RZP\Models\User\Entity | null
     */
    protected $user        = null;

    /**
     * @var boolean
     */
    protected $keylessPublicAuth = false;

    /**
     * The entity id with which keyless public auth happened
     * @var string
     */
    protected $keylessXEntityId;

    /**
     * Partner token parts received in the callback flow
     * Sample:
     * [
     *  'key' => 'rzp_test_partner_1DP5mmOlF5G5ag'
     *  'account_id' => 'acc_ACIg2tb8NySnuh'
     * ]
     *
     * @var array
     */
    public $partnerTokenCallbackData = [];

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

        $keyError = $this->checkAndSetKeyId($key);

        if ($keyError !== null)
        {
            return $keyError;
        }

        $this->authCreds->creds[self::SECRET] = $secret;

        $this->authCreds->creds[self::PUBLIC_KEY] = $key;

        return $this->checkAndSetAccountId();
    }

    public function checkAndSetKeyId($key)
    {
        $this->checkAndSetCreds($key);

        return $this->authCreds->validateAndSetKeyId($key);
    }

    protected function checkAndSetCreds(string $key)
    {
        $keyRegex = '/^rzp_(test|live)_(partner)_[a-zA-Z0-9]{14}$/';

        $validPartnerKey = (preg_match($keyRegex, $key, $matches) === 1);

        $this->isPartnerAuth = $validPartnerKey ?? false;

        $keyType = $validPartnerKey ? AuthCreds::CLIENT_ID : AuthCreds::API_KEY;

        $this->authCreds = new AuthCreds($this->app, $keyType, $key);
    }

    /**
     * If Account ID was sent, verify and set its value in $this->creds[]
     *
     * @param  string|null      $accountId
     * @return ApiResponse|null
     */
    protected function checkAndSetAccountId(string $accountId = null)
    {
        $accountId = $this->request->headers->get(RequestHeader::X_RAZORPAY_ACCOUNT);

        if (empty($accountId) === true)
        {
            return null;
        }

        if ($this->verifyAccountId($accountId) === false)
        {
            return $this->invalidAccountId($accountId);
        }

        $this->authCreds->creds[self::ACCOUNT_ID] = $accountId;

        return null;
    }

    /**
     * If Partner token was sent, verify and set its value in $this->creds[]
     *
     * @param  string|null      $token
     * @return ApiResponse|null
     */
    protected function checkAndSetPartnerToken(string $token = null)
    {
        if ($token === null)
        {
            return null;
        }

        if ($this->verifyAccountId($token) === false)
        {
            return $this->invalidPartnerToken($token);
        }

        $this->creds[self::PARTNER_TOKEN] = $token;

        $callbackKey = $this->getPublicKey() . self::PARTNER_CALLBACK_KEY_DELIMITER . $token;

        $this->authCreds->setPublicKey($callbackKey);

        return null;
    }

    /**
     * Tests if a given request has the partner token callback key
     *
     * Sample callback key for partner tokens:
     * rzp_partner_test_10000000000000
     *
     * @return bool
     */
    public function hasPartnerTokenCallbackKey(): bool
    {
        $key = $this->getKeyForNonBasicAuthTokens();

        $matches = [];

        // Sample token: rzp_test_partner_1DP5mmOlF5G5ag~acc_ACIg2tb8NySnuh
        $keyRegex = '/^(rzp_(test|live)_partner_[a-zA-Z0-9]{14})~(acc_[a-zA-Z0-9]{14})$/';

        $validCallbackKey = (preg_match($keyRegex, $key, $matches) === 1);

        if ($validCallbackKey === true)
        {
            $this->partnerTokenCallbackData = [
                self::KEY           => $matches[1],
                self::PARTNER_TOKEN => $matches[3],
            ];

            //
            // If the request was authenticated with key_id sent in the request params
            // we remove the key_id attribute before proceeding
            //
            $this->removeRequestKey(self::KEY_ID);
        }

        return $validCallbackKey;
    }

    /**
     * Handles public callback auth when a partner token is used
     */
    public function handlePartnerTokenOnPublicCallback()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $data = $this->partnerTokenCallbackData;

        $key          = $data[self::KEY];
        $partnerToken = $data[self::PARTNER_TOKEN];

        $this->creds[self::KEY]           = $key;
        $this->creds[self::PARTNER_TOKEN] = $partnerToken;

        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->authCreds->invalidApiKey();
        }

        $response = $this->authCreds->verifyKeyExistenceAndNotExpired();

        if ($response !== true)
        {
            return $response;
        }

        $this->authCreds->setPublicKey($key);
        $this->authCreds->fetchAndSetMerchantAndCheckLive();

        $error = $this->checkAndSetPartnerToken($partnerToken);

        if ($error !== null)
        {
            return $error;
        };

        $error = $this->checkAndSetPartnerMerchantScope();

        if ($error !== null)
        {
            return $error;
        };
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

        /**
         * Looks for basic auth api key first then client credentials
         * which are used like api key in case of partner accessing
         * on behalf of sub-merchant. The partner-merchant mapping is
         * verified at a later point.
         */
        if ($this->authCreds->isKeyExisting() === true)
        {
            $response = $this->authCreds->verifyKeyNotExpired();

            if ($response === true)
            {
                $response = $this->authCreds->verifySecret();
            }

            if ($response !== true)
            {
                return $response;
            }

            $error = $this->checkAndSetAccountScope();

            if ($error !== null)
            {
                return $error;
            }

            return $this->checkAndSetPartnerMerchantScope();
        }
        else if ($this->verifyInternalAppAsProxy() === true)
        {
            $this->setDashboardHeaders();

            $this->setProxyTrue();

            $this->setAdminAuthIfApplicable();

            return $this->checkAndSetAccountScope();
        }

        return $this->authCreds->invalidApiKey();
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $keyId = $this->request->input(self::KEY_ID);

        // Note: Attempts keyless auth in case when key_id request input exists
        // but is not set (i.e. is empty).
        if ((empty($keyId) === true) and (empty($this->request->getUser()) === true))
        {
            return $this->keylessPublicAuth();
        }
        else
        {
            return $this->keyPublicAuth();
        }
    }

    public function oauthPublicTokenAuth(string $token = null)
    {
        $this->setType(Type::PUBLIC_AUTH);

        $this->authCreds = new AuthCreds($this->app, AuthCreds::API_KEY, $token);

        $this->authCreds->setPublicKey($token);
    }

    public function setAuthCreds(AuthCreds $authCreds)
    {
        $this->authCreds = $authCreds;
    }

    /**
     * Handles keyless auth on public routes. Ref; KeylessPublicAuth.php
     *
     * @return mixed
     * @throws Exception\BadRequestException
     */
    public function keylessPublicAuth()
    {
        // Attempts to retrieve merchant and other attributes for ba via key less public auth approach
        list($mode, $merchant, $entityId) = (new KeylessPublicAuth)->retrieveModeMerchantAndXEntityId();

        // If we fail to retrieve merchant, return http auth expected exception
        if (($mode === null) or ($merchant === null) or ($entityId === null))
        {
            return ApiResponse::httpAuthExpected();
        }

        $this->setKeylessPublicAuthAttributes($entityId);

        // Sets the key instance if it exists, gets used in forming signature for payment authorize response
        $key = $this->repo->key->getLatestActiveKeyForMerchant($merchant->getId());

        $this->authCreds = new AuthCreds($this->app, AuthCreds::API_KEY, '');

        $this->authCreds->setKeyEntity($key);

        $this->authCreds->setAndCheckMerchantActivatedForLive($merchant);

        $this->authCreds->setModeAndDbConnection($mode);

        // Removes key_id from request if it existed with empty values
        $this->removeRequestKey(self::KEY_ID);
    }

    /**
     * Handles auth on public routes using public key id
     * @return mixed
     */
    public function keyPublicAuth()
    {
        if ($this->request->has(self::KEY_ID) === true)
        {
            $res = $this->setKeyFromQueryParams();
        }
        else
        {
            $res = $this->setCredentials();
        }

        // If there was any error response, return
        if ($res !== null)
        {
            return $res;
        }

        // Else continues with verifying key existence etc.. and sets all the instance variables accordingly
        $response = $this->authCreds->verifyKeyExistenceAndNotExpired();

        if ($response !== true)
        {
            return $response;
        }

        // Verify that no secret is being sent for public auth requests
        if (($this->authCreds->getSecret() !== '') and ($this->authCreds->getSecret() !== null))
        {
            return ApiResponse::generateErrorResponse(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE);
        }

        $this->authCreds->fetchAndSetMerchantAndCheckLive();

        return $this->checkAndSetPartnerMerchantScope();
    }

    public function directAuth()
    {
        $key = $this->request->input(self::KEY_ID);

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
        if (($this->isKeyBlank()) and ($this->verifyInternalApp()))
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

    protected function setKeylessPublicAuthAttributes(string $entityId)
    {
        $this->keylessPublicAuth = true;
        $this->keylessXEntityId  = $entityId;
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

            return $this->authCreds->invalidApiKey();
        }

        // `Route::$admin` contains routes that should strictly
        // be on admin auth and cannot be accessed over others
        // (proxy, internal, etc.)
        $currentRoute = $this->route->getCurrentRouteName();

        if (in_array($currentRoute, Route::$admin, true) === true)
        {
            return $this->authCreds->invalidApiKey();
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

        $response = $this->authCreds->verifyKeyExistenceAndNotExpired();

        if ($response !== true)
        {
            return $response;
        }

        $this->authCreds->fetchAndSetMerchantAndCheckLive();

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

        $key = $this->router->current()->parameter(self::KEY);

        if ($key === null)
        {
            $res = $this->setCredentials();

            if ($res !== null)
                return $res;
        }

        $this->authCreds->creds[self::SECRET] = null;
        $this->authCreds->creds[self::PUBLIC_KEY] = $key;

        // If key is wrong in formatting or something, send error back
        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->authCreds->invalidApiKey();
        }

        if ($this->authCreds->verifyKeyExistenceAndNotExpired() !== true)
        {
            return $this->authCreds->invalidApiKey();
        }

        $response = $this->authCreds->verifyKeyNotExpired();

        if ($response !== true)
        {
            return $response;
        }

        if (($this->getSecret() !== '') and ($this->getSecret() !== null))
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_SENT_ON_PUBLIC_ROUTE);
        }

        $this->authCreds->fetchAndSetMerchantAndCheckLive();
    }

// --------------------- Basic Auths Ends --------------------------------------

// --------------------- Verifiers ---------------------------------------------

    protected function verifyAccountId(string & $accountId)
    {
        try
        {
            Merchant\Account\Entity::verifyIdAndSilentlyStripSign($accountId);
        }
        catch (\Exception $e)
        {
            return false;
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
                TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $device = $this->repo->device->findByAuthToken($deviceToken);

        $this->device = $device;

        if (($device === null) or
            ($keyEntity->merchant->getId() !== $device->merchant->getId()))
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_INVALID_API_SECRET, [self::KEY_ID => $this->getKey()]);

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
        $merchantId = $this->authCreds->getKey();

        $merchant = $this->repo->merchant->find($merchantId);

        $this->authCreds->setMerchant($merchant);

        // If merchant id isn't found, then return false.
        return ($merchant !== null);
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
        if (in_array('*', $appRoutes, true) === true)
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

        $this->dashboardHeaders = [
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
        $secret = $this->authCreds->getSecret();

        $internalApps = $this->internalAppConfigs;

        $verify = false;

        foreach ($internalApps as $name => $info)
        {
            if ((isset($info[self::SECRET])) and
                ($info[self::SECRET] === $secret))
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
        return $this->creds[self::KEY];
    }

    private function getSecret()
    {
        return $this->creds[self::SECRET];
    }

    protected function getAccountId()
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->creds[self::ACCOUNT_ID] = $this->authCreds->creds[AuthCreds::ACCOUNT_ID];
        }

        return $this->creds[self::ACCOUNT_ID];
    }

    public function getPartnerToken()
    {
        return $this->creds[self::PARTNER_TOKEN];
    }

    public function getMode()
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->mode = $authCreds->getMode();
        }

        return $this->mode;
    }

    public function getKeyEntity()
    {
        return $this->authCreds->getKeyEntity();
    }

    public function getKeylessXEntityId()
    {
        return $this->keylessXEntityId;
    }

    public function getMerchant()
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->merchant = $authCreds->getMerchant();
        }
        return $this->merchant;
    }

    public function getMerchantId()
    {
        $merchant = $this->getMerchant();

        if (empty($merchant) === true)
        {
            return null;
        }

        return $merchant->getId();
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

    public function getAccessTokenId()
    {
        return $this->accessTokenId;
    }

    public function getOAuthClientId()
    {
        return $this->oauthClientId;
    }

    public function getPartnerMerchantId()
    {
        return $this->partnerMerchantId;
    }

    public function getPublicKey()
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->creds[self::PUBLIC_KEY] = $authCreds->creds[AuthCreds::PUBLIC_KEY];
        }
        return $this->creds[self::PUBLIC_KEY];
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
        return ($this->getInternalApp() === 'dashboard');
    }

    public function isCron()
    {
        return ($this->getInternalApp() === 'cron');
    }

    public function getOAuthApplicationId()
    {
        return $this->applicationId;
    }

// --------------------- Getters Ends ------------------------------------------

// --------------------- Setters -----------------------------------------------

    public function setMode(string $mode)
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->authCreds->setMode($mode);

            $this->mode = $this->authCreds->getMode();
        }

        $this->app['rzp.mode'] = $mode;
    }

    public function setModeAndDbConnection(string $mode)
    {
        $this->setMode($mode);

        \Database\DefaultConnection::set($mode);
    }

    public function setAccessTokenId(string $tokenId)
    {
        $this->accessTokenId = $tokenId;
    }

    public function setOAuthClientId(string $oauthClientId)
    {
        $this->oauthClientId = $oauthClientId;
    }

    public function setOAuthApplicationId(string $applicationId)
    {
        $this->applicationId = $applicationId;
    }

    public function setPartnerMerchantId(string $merchantId)
    {
        $this->partnerMerchantId = $merchantId;
    }

    public function setMerchant($merchant)
    {
        if ($merchant !== null)
        {
            $this->setOrgId($merchant->org->getPublicId());
        }

        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->authCreds->setMerchant($merchant);

            $this->merchant = $this->authCreds->getMerchant();
        }
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
        $this->creds[self::PUBLIC_KEY] = $publicKey;
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

    // --------------------- Setters Ends ---------------------

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

    public function isKeylessPublicAuth()
    {
        return (($this->isPublicAuth() === true) and ($this->keylessPublicAuth === true));
    }

    public function isPrivateAuth()
    {
        return ($this->type === Type::PRIVATE_AUTH);
    }

    public function isStrictPrivateAuth()
    {
        return (($this->isPrivateAuth() === true) and ($this->isProxyAuth() === false));
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
        $key = $this->request->input(self::KEY_ID);

        // If not provided, then send error asking for it
        if (($key === null) or
            ($key === ''))
        {
            return ApiResponse::provideApiKey();
        }

        $this->viaQueryParams = true;

        // Remove 'key_id' from query params
        $this->removeRequestKey(self::KEY_ID);

        // If key is wrong in formatting or something, send error back
        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->invalidApiKey();
        }

        $this->authCreds->creds[self::SECRET] = null;
        $this->authCreds->creds[self::PUBLIC_KEY] = $key;

        return $this->checkAndSetAccountId();
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
            return null;
        }

        $account = $this->repo
                        ->merchant
                        ->find($this->getAccountId());

        if (($account === null) or
            ($this->validateAccountForCurrentAuthType($account) === false))
        {
            return $this->invalidAccountId($this->getAccountId());
        }

        $this->authCreds->setMerchant($account);
    }

    /**
     * 1. Check if merchant is marked as a partner
     * 2. Set partner merchant id in the auth context
     * 3. Fetch sub-merchant token from header
     * 4. Set current merchant as sub-merchant
     * 5. Check sub-merchant activated for live (We don't
     *    care about partner merchant activation here.)
     *
     * @return null
     */
    protected function checkAndSetPartnerMerchantScope()
    {
        if ($this->isPartnerAuth === false)
        {
            return;
        }

        $accountId = $this->getAccountId();

        if ($accountId === '')
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_PARTNER_ACCOUNT_ID_REQUIRED);
        }

        if ($this->isPartnerAuthAllowed() === false)
        {
            return ApiResponse::unauthorized(
                ErrorCode::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED);
        }

        $account = $this->repo
                        ->merchant
                        ->find($accountId);

        if (empty($account) === true)
        {
            return $this->invalidAccountId($accountId);
        }

        $this->setPartnerMerchantId($this->authCreds->getMerchant()->getId());

        $this->authCreds->setAndCheckMerchantActivatedForLive($account);

        if ($this->isPartnerMerchantMapped($this->authCreds->getMerchant()->getId(), $this->getPartnerMerchantId()) === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }
    }

    protected function isPartnerMerchantMapped(string $merchantId, string $partnerId)
    {
        $app = (new OAuthApp\Repository)->findActivePartnerApplicationByMerchantId($partnerId);

        $mapping = (new Merchant\AccessMap\Repository)
                        ->findMerchantAccessMapOnEntityId($merchantId, $app->getId(), 'application');

        return (empty($mapping) === false);
    }

    protected function isPartnerAuthAllowed(): bool
    {
        $partnerMerchant = $this->authCreds->getMerchant();
        //
        // $this->merchant needs to have been set, and have been tagged as 'partner'
        //
        if ((empty($partnerMerchant) === true) or ($partnerMerchant->isPartner() === false))
        {
            return false;
        }

        // Only allow partner token auth on public and private auth, no proxy
        if (($this->isStrictPrivateAuth() === false) and
            ($this->isPublicAuth() === false))
        {
            return false;
        }

        return true;
    }

    public function setAndCheckMerchantActivatedForLive(Merchant\Entity $merchant)
    {
        $this->setMerchant($merchant);
        $this->checkMerchantActivatedForLive();
    }

    public function checkMerchantActivatedForLive()
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
            TraceCode::BAD_REQUEST_INVALID_API_KEY, [self::KEY_ID => $this->getKey()]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }

    protected function invalidAccountId(string $accountId)
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_ACCOUNT_HEADER,
            [
                self::AUTH_TYPE  => $this->getAuthType(),
                self::KEY_ID     => $this->getKey(),
                self::ACCOUNT_ID => $accountId,
            ]);

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
    }

    protected function invalidPartnerToken(string $token)
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_PARTNER_TOKEN_HEADER,
            [
                self::AUTH_TYPE     => $this->getAuthType(),
                self::KEY_ID        => $this->getKey(),
                self::PARTNER_TOKEN => $token,
            ]);

        return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_PARTNER_TOKEN);
    }

    protected function isKeyBlank()
    {
        return ($this->authCreds->creds['key'] === '');
    }

    public function sign($str)
    {
        $key = $this->getKeyEntity();

        if (($key === null) and ($this->oauthClientId === null))
        {
            throw new Exception\LogicException('Key cannot be null here');
        }

        if (($this->oauthClientId === null) === false)
        {
            $client = (new OAuthClient\Repository)->findOrFail($this->oauthClientId);

            $secret = 'TheKeySecretForTests';//$client->getSecret();
        }
        else
        {
            $secret = Crypt::decrypt($key->getSecret());
        }

        return hash_hmac(self::HMAC_ALGO, $str, $secret);
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
        if (($this->isPrivateAuth() === true) and
            (empty($this->authCreds->getMerchant()) === false) and
            ($this->authCreds->getMerchant()->isMarketplace() === true))
        {
            return true;
        }

        return false;
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
                return ($account->getParentId() === $this->authCreds->getMerchant()->getId());

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

    /**
     * Sets User Entity
     *
     * @param \RZP\Models\User\Entity $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Returns User or null based on the X-Dashboard-User-Id header
     *
     * @return null|\RZP\Models\User\Entity
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Verifies and sets user from the headers.
     */
    public function verifyAndSetUser()
    {
        $dashboardHeaders = $this->getDashboardHeaders();

        $userId = $dashboardHeaders['user_id'] ?? null;

        if (empty($userId) === false)
        {
            $user = $this->repo->user->findOrFailPublic($userId);

            $this->setUser($user);
        }
    }

    public function getKeyForNonBasicAuthTokens()
    {
        // Check `key_id` first, else fallback to BasicAuth user
        $keyParam = $this->request->input(self::KEY_ID);
        $key      = $keyParam ?? $this->request->getUser();

        // For callback routes, gets the key from route parameter
        $route = $this->router->currentRouteName();
        if ((empty($key) === true) and (in_array($route, Route::$publicCallback, true) === true)) {
            $key = $this->router->current()->parameter(self::KEY);
        }

        return $key;
    }

    public function removeRequestKey(string $key)
    {
        $this->request->query->remove($key);
        $this->request->request->remove($key);
    }
}
