<?php

namespace RZP\Http;

use Illuminate\Http\Request;

use RZP\Models\Key;
use RZP\Constants\Mode;
use Lcobucci\JWT\Token;
use RZP\Error\ErrorCode;
use RZP\Http\BasicAuth\Type;
use RZP\Foundation\Application;
use RZP\Base\RepositoryManager;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Http\BasicAuth\AuthCreds;
use Lcobucci\JWT\Encoding\JoseEncoder;
use RZP\Exception\BadRequestException;
use RZP\Trace\TraceCode;

/**
 * @deprecated ...in favor of RequestContextV2, would take around one year time though.
 * Extracts and holds various variables from request to be used in throttling and subsequent middle-wares.
 * This only does minimal database/redis calls, which is required even by throttle(first middleware) module.
 */
final class RequestContext
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var string
     */
    protected $route;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RepositoryManager
     */
    protected $repo;

    /**
     * Trace instance used for tracing
     * @var \Razorpay\Trace\Logger
     */
    protected $trace;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $secret;

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var string
     */
    protected $auth;

    /**
     * @var bool
     */
    protected $isRunningUnitTests;

    /**
     * @var array
     */
    protected $applications;

    /**
     * @var array
     */
    protected $baApplications;

    /**
     * @var string
     */
    protected $authFlowType;

    /**
     * @var string
     */
    protected $keySource;

    /**
     * @var string
     */
    protected $accountIdSource;

    //
    // In one request some (and not all) of below identifiers are set. Further
    // in throttle core logic we construct throttle key using the one available.
    //

    /**
     * @var string
     */
    protected $keyWithoutPrefix;

    /**
     * @var string
     */
    protected $keyId;

    /**
     * @var Key\Entity
     */
    protected $keyEntity;

    /**
     * @var string
     */
    protected $mid;

    /**
     * Every oauth application has dev & prod clients.
     * @var string
     */
    protected $oauthClientId;

    /**
     * @var string
     */
    protected $oauthPublicToken;

    /**
     * @var string
     */
    protected $bearerToken;

    /**
     * @var string
     */
    protected $internalAppName;

    /**
     * @var string
     */
    protected $adminEmail;

    /**
     * @var bool
     */
    protected $proxy = false;

    /**
     * Dashboard user id (from headers) in case of proxy auth
     * @var null|string
     */
    protected $userId;

    /**
     * Dashboard user's 2FA verification status (from headers) in case of proxy auth
     * @var bool
     */
    protected $user2FaVerified;

    /**
     * payment method, eg: upi, cards, nb
     * @var string
     */
    protected $paymentMethod;

    /**
     * payment method, eg: upi, cards, nb
     * This is generated at start of request and for all payment routes
     * should be generated. $paymentMethod is generated only for
     * gatewayExceptions and for bad request or server exception isn't generated
     * This is used to throw error metrics with method as dimension
     * Initialising with empty string as gives null error when it is not set
     * @var string
     */
    protected string $paymentMethodV2 = "";

    /**
     * To check if request context is already initialized or not.
     * @var null|string
     */
    protected $initialized = false;

    /**
     * @var RequestContextV2
     */
    protected $reqCtxV2;

    protected static Token\Parser $parser;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function init()
    {
        $env = $this->app->environment();

        if ($env === 'testing' or $this->initialized === false)
        {
            $this->trace = $this->app['trace'];
            $this->initInstanceVars();
            $this->setAuthVars();
            $this->initialized = true;
        }

        self::$parser    = new Token\Parser(new JoseEncoder());
    }

    // There are few cases where getRoute returns null
    // ex: in case workers
    public function getRoute()
    {
        return $this->route;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getKeyEntity()
    {
        return $this->keyEntity;
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function getMode()
    {
        return $this->mode;
    }

    // Todo: Refer: Route.php's $skipThrottling.
    public function getAuth()
    {
        return $this->auth;
    }

    public function getKeySource()
    {
        return $this->keySource;
    }

    public function getAccountIdSource()
    {
        return $this->accountIdSource;
    }

    public function getKeyWithoutPrefix()
    {
        return $this->keyWithoutPrefix;
    }

    public function getKeyId()
    {
        return $this->keyId;
    }

    public function getMid()
    {
        return $this->mid;
    }

    public function getOAuthClientId()
    {
        return $this->oauthClientId;
    }

    public function getOAuthPublicToken()
    {
        return $this->oauthPublicToken;
    }

    public function getBearerToken()
    {
        return $this->bearerToken;
    }

    public function getInternalAppName()
    {
        return $this->internalAppName;
    }

    public function getAdminEmail()
    {
        return $this->adminEmail;
    }

    public function getProxy(): bool
    {
        return $this->proxy;
    }

    public function getUserId()
    {
        return $this->userId;
    }

    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function isDashboard(): bool
    {
        return (($this->internalAppName === 'dashboard') or
                ($this->isAdminDashboard() === true) or
                ($this->isMerchantDashboard() === true));
    }

    public function isAdminDashboard(): bool
    {
        return ($this->internalAppName === 'admin_dashboard');
    }

    public function isMerchantDashboard(): bool
    {
        return ($this->internalAppName === 'merchant_dashboard');
    }

    public function isPublicAuth(): bool
    {
        return ($this->auth === Type::PUBLIC_AUTH);
    }

    public function isDirectAuth(): bool
    {
        return ($this->auth === Type::DIRECT_AUTH);
    }

    public function getAuthFlowType()
    {
        return $this->authFlowType;
    }

    public function getUser2FAVerified(): bool
    {
        return $this->user2FaVerified;
    }

    public function getBearerTokenFromRequest()
    {
        return $this->isRunningUnitTests ? $this->request->bearerToken() : $this->getBearerTokenFromRequestForApache();
    }

    public function getBearerTokenFromRequestForApache(): string
    {
        $headers = getallheaders()['Authorization'] ?? getallheaders()['authorization'] ?? null;

        return (starts_with($headers, 'Bearer ') ? substr($headers, 7) : '');
    }

    public function isKeyOAuthPublicToken(): bool
    {
        return ((strlen($this->key) === OAuth::PUBLIC_TOKEN_LENGTH) and (substr($this->key, 8, 7) === '_oauth_'));
    }

    protected function setKeySource()
    {
        if (!empty($this->request->query('key_id')) === true)
        {
            $this->keySource = BasicAuth::QUERY_PARAM;
        }
        else if (!empty($this->request->input('key_id')) === true)
        {
            $this->keySource = BasicAuth::BODY_PARAM;
        }
        else if (!empty($this->request->getUser()) === true) {
            $this->keySource = BasicAuth::AUTH_HEADER;
        }
    }

    public function setAccountIdSource()
    {
        if (!empty($this->request->headers->get(RequestHeader::X_RAZORPAY_ACCOUNT))) {
            $this->accountIdSource = BasicAuth::AUTH_HEADER;
        }
        else if (!empty($this->request->query('account_id')) === true)
        {
            $this->accountIdSource = BasicAuth::QUERY_PARAM;
        }
        else if (!empty($this->request->input('account_id')) === true)
        {
            $this->accountIdSource = BasicAuth::BODY_PARAM;
        }
    }

    public function isDashboardGuest(): bool
    {
        return ($this->internalAppName === 'dashboard_guest');
    }

    public function isAuthService(): bool
    {
        return ($this->internalAppName === 'auth_service');
    }

    /**
     * Checks weather auth flow type is one of which is handled in nginx sidecar throttling layer
     * Method used to skip throttling in api throttler middleware
     * @return bool
     */
    public function isNginxHandledAuthFlowType(): bool
    {
        $authFlowTypes = [BasicAuth::KEY, BasicAuth::OAUTH];

        return in_array($this->authFlowType, $authFlowTypes, true) === true;
    }

    /**
     * Checks weather auth type is one of which is handled in nginx sidecar throttling layer
     * Method used to skip throttling in api throttler middleware
     * @return bool
     */
    public function isNginxHandledAuthType(): bool
    {
        $authTypes = [Type::PUBLIC_AUTH, Type::PROXY_AUTH, Type::PRIVATE_AUTH];

        return in_array($this->auth, $authTypes, true) === true;
    }

    /**
     * Protected Methods
     */

    /**
     * Initializes various instance variables - e.g. request, repo etc. This needs to be done outside the construction
     * of this service because otherwise in testing environment it will continue pointing to same first request instance.
     * In some tests we are making multiple api calls (which internally is direct kernel's call() method).
     */
    protected function initInstanceVars()
    {
        $this->request            = $this->app['request'];
        $this->repo               = $this->app['repo'];
        $this->isRunningUnitTests = $this->app->runningUnitTests();
        $this->applications       = $this->app['config']->get('applications');
        $this->baApplications     = $this->app['config']->get('applications_v2');
        $this->route              = null;
        $this->key                = null;
        $this->secret             = null;
        $this->mode               = null;
        $this->auth               = null;
        $this->keyWithoutPrefix   = null;
        $this->keyId              = null;
        $this->keyEntity          = null;
        $this->mid                = null;
        $this->oauthClientId      = null;
        $this->oauthPublicToken   = null;
        $this->bearerToken        = null;
        $this->internalAppName    = null;
        $this->adminEmail         = null;
        $this->proxy              = false;
        $this->userId             = null;
        $this->authFlowType       = BasicAuth::KEY;
        $this->reqCtxV2           = $this->app['request.ctx.v2'];
    }

    /**
     * Extracts information from requests as per route's auth group.
     * @throws BadRequestException
     */
    protected function setAuthVars()
    {
        $this->route = $this->request->route()->getName();

        if ($this->setAdditionalVarsForPrivilegeAuth() == true)
        {
            $this->auth = Type::PRIVILEGE_AUTH;
        }
        else if ($this->setAdditionalVarsForPrivateAuth() == true)
        {
            $this->auth = Type::PRIVATE_AUTH;
        }
        else if ($this->setAdditionalVarsForPublicAuth() == true)
        {
            $this->auth = Type::PUBLIC_AUTH;
        }
        else if ($this->setAdditionalVarsForDeviceAuth() == true)
        {
            $this->auth = Type::DEVICE_AUTH;
        }
        else if ($this->setAdditionalVarsForP2pDeviceAuth() == true)
        {
            $this->auth = Type::DEVICE_AUTH;
        }
        else if ($this->setAdditionalVarsForDirectAuth() == true)
        {
            $this->auth = Type::DIRECT_AUTH;
        }
        else if ($this->setAdditionalVarsForP2pDirectAuth() === true)
        {
            $this->auth = Type::DIRECT_AUTH;
        }
        else if ($this->setAdditionalVarsForApiStatus() == true)
        {
            // do nothing
        }
        else
        {
            throw new BadRequestException(ErrorCode::BAD_REQUEST_URL_NOT_FOUND);
        }
    }

    /**
     * Resolves $keyId and sets $keyEntity as well as $mid instance
     */
    public function resolveKeyIdIfApplicable()
    {
        if ((empty($this->keyId) === true) or
            (empty($this->mode) === true) or
            (Mode::exists($this->mode) === false))
        {
            return;
        }

        //
        // Skipping this for partner auth for now. We have to fix on whether the client's merchant should be throttled
        // or the account passed in the input/header i.e. the sub merchant.
        //
        if ($this->isPartnerAuth())
        {
            $this->authFlowType = BasicAuth::PARTNER;
            return;
        }

        //
        // Note: We don't do a findOrFail() here. That is the responsibility of basic auth. This class only deals with
        // initializing all the request context variables. For e.g. in case of invalid keyId, keyEntity would just be
        // null. It's up to next layers - Authenticate to throw errors etc.
        //
        app()['rzp.mode'] = $this->mode;
        $this->keyEntity = $this->repo->key->connection($this->mode)->find($this->keyId);
        $this->mid = optional($this->keyEntity)->getMerchantId();
    }

    protected function setAdditionalVarsForPublicAuth()
    {
        $isPublicRoute         = in_array($this->route, Route::$public, true);
        $isP2pPublicRoute      = in_array($this->route, P2pRoute::$public, true);
        $isPublicCallbackRoute = in_array($this->route, Route::$publicCallback, true);

        // Route belongs neither to public or public callback group
        if (($isPublicRoute === false) and ($isP2pPublicRoute === false) and ($isPublicCallbackRoute === false))
        {
            return false;
        }

        // For callback routes, attempt getting key from route parameter first.
        if ($isPublicCallbackRoute === true)
        {
            $this->keySource = BasicAuth::ROUTE_PARAM;
            $passportKey = $this->getPassportKey('publicKey');
            $key = $passportKey ?? $this->request->route()->parameter('key_id');
        }
        // Else check key_id first, else fallback to auth user.
        if (empty($key) === true)
        {
            $this->setKeySource();
            $key = $this->request->input('key_id') ?? $this->request->getUser();
        }

        $this->setKeyModeAndSecret($key);


        // Route belongs to one of 2 groups and accessed via oauth public token
        if ($this->isKeyOAuthPublicToken() === true)
        {
            // Further excludes "oauth_" part
            $this->oauthPublicToken = substr($this->keyWithoutPrefix, 6);
            $this->authFlowType = BasicAuth::OAUTH;
        }
        // Route belongs to one of 2 groups and accessed normally via key id
        else
        {
            $this->keyId = $this->keyWithoutPrefix;
        }

        return true;
    }

    protected function setAdditionalVarsForPrivateAuth()
    {
        $isPrivateRoute = (in_array($this->route, Route::$private, true) or
                          (in_array($this->route, P2pRoute::$private, true)));
        $isProxyRoute   = in_array($this->route, Route::$proxy, true);

        self::$parser = new Token\Parser(new JoseEncoder());

        if (($isPrivateRoute === true) and (empty($token = $this->getBearerTokenFromRequest()) === false))
        {
            $parsed              = self::$parser->parse($token);

            if (empty($parsed->claims()->get('aud')) === false)
            {
                $this->oauthClientId = $parsed->claims()->get('aud')[0];
            }

            $this->mid           = $parsed->claims()->get('merchant_id');

            $this->authFlowType = BasicAuth::OAUTH;

            return true;
        }

        $this->setKeyModeAndSecret();

        // In case of proxy auth(even for private routes), internal app is dashboard and the same needs to be set
        $this->setInternalAppNameByAuth();

        if ((($isPrivateRoute === true) and ($this->isDashboard() === true)) or
            (($isProxyRoute === true) and ($this->isPartnerAuth() === false)))
        {
            $this->mid  = $this->keyWithoutPrefix;
            $this->proxy = true;
            $this->userId = $this->request->headers->get(RequestHeader::X_DASHBOARD_USER_ID);

            $this->user2FaVerified = $this->request->headers->get(RequestHeader::X_DASHBOARD_USER_2FA_VERIFIED) === 'true';

            return true;
        }
        else if (($isPrivateRoute === true) and ($this->isDashboard() === false))
        {
            $this->keyId = $this->keyWithoutPrefix;

            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForDirectAuth()
    {
        // use passport values if shouldAuthenticateUsingPassport is true
        $passportKey = $this->getPassportKey();
        $key = $passportKey ?? $this->request->input(BasicAuth::KEY_ID);

        if (empty($key) === false)
        {
            $this->authFlowType = BasicAuth::PUBLIC;
        }

        return in_array($this->route, Route::$direct, true);
    }

    protected function setAdditionalVarsForP2pDirectAuth(): bool
    {
        $this->setKeyModeAndSecret();

        return in_array($this->route, P2pRoute::$direct, true);
    }

    protected function setAdditionalVarsForPrivilegeAuth()
    {
        $this->setKeyModeAndSecret();

        if (in_array($this->route, Route::$internal, true) === true)
        {
            $this->setInternalAppNameByAuth();

            if (empty($this->internalAppName)) {
                $this->setInternalAppNameByPassport();
            }

            return true;
        }
        else if (in_array($this->route, Route::$admin, true) === true)
        {
            $this->setInternalAppNameByAuth();

            $this->adminEmail = $this->request->headers->get(RequestHeader::X_DASHBOARD_ADMIN_EMAIL);

            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForDeviceAuth()
    {
        $this->setKeyModeAndSecret();

        if (in_array($this->route, Route::$device, true) === true)
        {
            $this->keyId = $this->keyWithoutPrefix;
            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForP2pDeviceAuth()
    {
        $this->setKeyModeAndSecret();

        if (in_array($this->route, P2pRoute::$device, true) === true)
        {
            return true;
        }

        return false;
    }

    protected function setAdditionalVarsForApiStatus()
    {
        if ($this->route === 'api_status')
        {
            return true;
        }

        return false;
    }

    public function setPaymentMethod($paymentMethod)
    {
        $this->paymentMethod = $paymentMethod;
    }

    /**
     * Sets internal app name using passport
     * Executes only if old flow wasn't able to set app name
     */
    protected function setInternalAppNameByPassport()
    {
        $hasPassportJwt = $this->app['request.ctx.v2']->hasPassportJwt;
        $passport = $this->app['request.ctx.v2']->passport;

        if (!$hasPassportJwt or !$passport or !$passport->consumer or !$passport->consumer->id
            or $passport->consumer->type !== BasicAuth::PASSPORT_CONSUMER_TYPE_APPLICATION)
        {
            return;
        }

        $appId = $passport->consumer->id;
        // No app config present for the given application_id
        if (!array_key_exists($appId, $this->baApplications)) {
            return;
        }

        $config = $this->baApplications[$appId];
        if (!is_array($config) or !array_key_exists('name', $config) or
            !is_string($config['name']) or empty($config['name'])) {
            return;
        }

        $this->internalAppName = $config['name'];
    }

    /**
     * Sets internalAppName value by checking auth's secret value.
     */
    protected function setInternalAppNameByAuth()
    {
        foreach ($this->applications as $name => $config)
        {
            if ((empty($config['secret']) === false) and ($config['secret'] === $this->secret))
            {
                $this->internalAppName = $name;

                return;
            }
        }
    }

    protected function setKeyModeAndSecret($key = null, $secret = null)
    {
        // use passport values if shouldAuthenticateUsingPassport is true
        $passportKey = $this->getPassportKey();
        $key = $key ?? ($passportKey ?? $this->request->getUser());
        // If key is empty (direct auth & bearer token case) or is of invalid length just return from this method.
        if ($this->isKeyOfValidLength($key) === false)
        {
            return;
        }

        $this->key              = $key;
        $this->keyWithoutPrefix = substr($this->key, 9) ?: null;
        $this->secret           = $secret ?? $this->request->getPassword();

        $passportMode = $this->reqCtxV2->shouldAuthenticateUsingPassport ? $this->reqCtxV2->passport->mode : null;
        $mode         = $passportMode ?? substr($this->key, 4, 4) ?: null;
        $this->mode = Mode::exists($mode) ? $mode : null;
    }

    /**
     * sets auth flow type
     *
     * @param string   $authFlowType
     */
    public function setAuthFlowType(string $authFlowType): void
    {
        $this->authFlowType = $authFlowType;
    }

    /**
     * @param  string|null $key
     * @return bool
     */
    protected function isKeyOfValidLength($key): bool
    {
        $validKeyLengths = array_merge(AuthCreds::$validKeyLengths, [OAuth::PUBLIC_TOKEN_LENGTH]);

        return (in_array(strlen($key), $validKeyLengths, true) === true);
    }

    /**
     * Returns true if request is using partner creds
     *
     * @return bool
     */
    protected function isPartnerAuth()
    {
        return $this->reqCtxV2->shouldAuthenticateUsingPassport ? ($this->reqCtxV2->passport->consumer->type == 'partner') : str_contains($this->keyWithoutPrefix, 'partner_');
    }

    /**
     * Returns key used for the request from passport if passport is usable else null
     *
     * @param string $param allowed params [username, publicKey]
     * @return string|null
     */
    protected function getPassportKey(string $param = 'username')
    {
        return $this->reqCtxV2->shouldAuthenticateUsingPassport ? $this->reqCtxV2->passport->credential->{$param} : null;
    }

    public function getPaymentMethodV2(): string
    {
        return $this->paymentMethodV2;
    }

    public function isPaymentCreateRoute(): bool
    {
        $currentRoute = $this->app['router']->currentRouteName();
        return str_starts_with($currentRoute, 'payment_create');
    }

    /**
     * Setting payment method in the RequestContext by extracting it from the request body
     * for payment create routes only
     * @param $data array Data holds current request
     * @return void
     */
    public function setPaymentMethodV2InRequestContext(array $data): void
    {
        if (!$this->isPaymentCreateRoute())
        {
            return;
        }

        if (!empty($data['method']))
        {
            $this->paymentMethodV2 = $data['method'];
        }
    }
}
