<?php

namespace RZP\Http\BasicAuth;

use Crypt;
use Config;
use ApiResponse;
use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Token\Builder as JWTBuilder;
use Lcobucci\JWT\Signer as JWTSigner;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Razorpay\OAuth\OAuthServer;
use RZP\Constants\HyperTrace;
use RZP\Error\PublicErrorDescription;
use RZP\Exception;
use RZP\Http\Edge\Metric;
use RZP\Http\OAuthScopes;
use RZP\Http\RequestContextV2;
use RZP\Http\Route;
use RZP\Models\Application\Entity;
use RZP\Models\Key;
use RZP\Models\Admin;
use RZP\Models\Batch;
use RZP\Models\Device;
use RZP\Constants\Mode;
use RZP\Models\Feature;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Product;
use RZP\Http\RequestHeader;
use RZP\Models\EntityOrigin;
use RZP\Base\RepositoryManager;
use RZP\Exception\LogicException;
use RZP\Models\User\Entity as User;
use RZP\Models\Batch\Entity as BatchEntity;
use RZP\Models\User\Service as UserService;
use RZP\Models\Merchant\Account\Entity as Account;

use Razorpay\OAuth\Client as OAuthClient;
use RZP\Trace\Tracer;

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
     * rzp_test_1DP5mmOlF5G5ag-rzp_partner_ACIg2tb8NySnuh
     *
     * Delimiter used is defined in this const.
     */
    const PARTNER_CALLBACK_KEY_DELIMITER = '-';

    const KEY                     = 'key';
    const KEY_ID                  = 'key_id';
    const MERCHANT_ID             = 'merchant_id';
    const PARTNER_ID              = 'partner_id';
    const ENTITY_ID               = 'entity_id';
    const ACCOUNT_ID              = 'account_id';
    const SECRET                  = 'secret';
    const PUBLIC_KEY              = 'public_key';

    // Used in requestContext for request metrics
    const OAUTH                 = 'oauth';
    const PARTNER               = 'partner';
    const PUBLIC                = 'public';

    // Used in logs to detect which auth was used.
    // Route list will not help identify the above as there is a fallback mechanism added apart from route lists.
    const KEYLESS_AUTH          = 'keyless_auth';
    const KEY_AUTH              = 'key_auth';
    const DIRECT_AUTH           = 'direct_auth';
    const AUTH_TYPE             = 'auth_type';

    const ROUTE_PARAM           = 'route_param';
    const QUERY_PARAM           = 'query_param';
    const BODY_PARAM            = 'body_param';
    const AUTH_HEADER           = 'auth_header';

    const ROUTE                 = 'route';
    const EDGE_CONSUMER         = 'edge_consumer';

    // Public key used in public auth, and callback route param can be one of these forms.
    const PARTNER_KEY_REGEX = '/^(rzp_(test|live)_partner_([a-zA-Z0-9]{14}))[-~](acc_[a-zA-Z0-9]{14})$/';
    const OAUTH_KEY_REGEX   = '/^(rzp_(test|live)_oauth_[a-zA-Z0-9]{14}).*$/';
    const KEY_REGEX         = '/^rzp_(test|live)_([a-zA-Z0-9]{14})$/';

    // Ref $passport
    const PASSPORT_CONSUMER_TYPE_MERCHANT            = 'merchant';
    const PASSPORT_CONSUMER_TYPE_APPLICATION         = 'application';
    const PASSPORT_IMPERSONATION_TYPE_PARTNER        = 'partner';
    const PASSPORT_IMPERSONATION_TYPE_USER_MERCHANT  = 'user_merchant';
    const PASSPORT_IMPERSONATION_TYPE_ADMIN_MERCHANT = 'admin_merchant';
    const PASSPORT_OAUTH_OWNER_TYPE_MERCHANT         = 'merchant';
    const PASSPORT_CONSUMER_TYPE_ADMIN               = 'admin';
    const PASSPORT_CONSUMER_TYPE_USER                = 'user';

    const CONTENT_TYPE                               = 'CONTENT_TYPE';

    const HTTP_METHOD                                = 'method';

    const HTTP_CONTENT_TYPE                          = 'content_type';

    // All dashboard applications
    const DASHBOARD_APPS                             = ['admin_dashboard', 'merchant_dashboard', 'dashboard', 'dashboard_guest', 'frontend_graphql',];

    const X_DASHBOARD_APPS                           = ['merchant_dashboard', 'dashboard'];

    const WHITE_LISTED_MIDS_FOR_ACCOUNT_ID_IN_BODY   = ['hoah6c9snynis5','EVPKynANo94brO','HL0XeiZ1v1kyUZ','Jd6fYXxU7jjAWt','G8X2PlQPEqR9jg','FIMutKDXBwL9fN','6N5ssOOKSLBIES','DT2WnV8uxRDwjO','KHt6aG32DgGLTd','EgzuLu9uMZEgP4','D8yeOZdLluPZyA','Jq5FbjcohIEPoe','FIMutKDXBwL9fN','HJH6H4wTaaVe5x'];

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
    protected $applicationId = null;

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
     * @var array|null
     */
    protected $tokenScopes;

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
     * This is an instance of an AuthCreds implementation. AuthCreds was added to abstractly handle parts of
     * authentication for multiple flows e.g. usual key auth, and partner client auth.
     *
     * Notice! This is not initialized as part of constructor of init. It is initialized basis request attributes,
     * after invoke of various handlers e.g. in publicAuth, privateAuth and so on- specifically in function
     * checkAndSetKeyId. So please ensure initialization before using it.
     *
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
     * current Admin entity
     *
     * @var Admin\Admin\Entity
     */
    protected $admin = null;

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
     * @deprecated
     * Array of configurations of internal applications
     * Used by the old app auth flow to authenticate
     * @var array
     */
    protected $internalAppConfigs;

    /**
     * Array of configurations of internal applications
     * Used by the new BasicAuth app authentication.
     * @var array
     */
    protected $internalBasicAuthAppConfigs;

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
     * Request Origin Product gives the Product information (payment gateway or business banking).
     *
     * @var string
     */
    protected $requestOriginProduct = Product::PRIMARY;

    /**
     * Product gives the Product information (payment gateway or business banking).
     * This is derived using $requestOriginProduct and some other parameters.
     * Refer UserAccess::setProduct()
     * Useful for tagging logs with respective product info
     * Refer ApiTraceProcessor::addProduct()
     *
     * @var string
     */
    protected $product = Product::PRIMARY;

    /**
     * Array of dashboard headers
     * @var array
     */
    protected $dashboardHeaders = array();

    protected $adminOrgId  = null;

    protected $adminToken;

    protected $orgId       = null;

    /**
     * If admin's organisation has cross route enabled, admin will have access to other organisations as well. In this
     * case, admin may have cross Id. Cross id is the id of the organisation to which admin want to access.
     */
    protected $crossOrgId;

    protected $orgHostName = null;

    protected $orgType     = null;

    /**
     * User is set from the id received in X-Dashboard-User-Id header.
     *
     * @var \RZP\Models\User\Entity | null
     */
    protected $user        = null;

    /**
     * User Role is a role associated to the merchant for the user.
     */
    protected $userRole    = null;

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
    public $partnerAuthCallbackData = [];

    /**
     * Sets context data when the batch flow is being executed
     *
     * @var array
     */
    protected $batchRequestContext = [];

    /**
     * Sets the batch object when the batch flow is being executed
     *
     * @var Batch\Entity
     */
    protected $batch;

    /**
     * @var string
     */
    protected $idempotencyKeyId = null;

    /**
     *  Routes which are allowed to pass X-Razorpay-Account
     * @var array
     */
    protected $whitelistRoutesForReferrerPartnerAccess = [
        'merchant_activation_save',
        'merchant_activation_details',
        'merchant_document_upload',
        'merchant_document_delete',
        'merchant_store_add',
        'merchant_store_fetch',
        'merchant_activation_clarifications_save',
        'merchant_activation_clarifications_fetch',
        'merchant_save_business_website',
        'merchant_website_section_action',
        'fetch_merchant_escalation',
        'merchant_fetch_config',
        'merchant_activation_gst_details',
        'merchant_document_url_fetch',
        'merchant_nc_revamp_eligibility',
    ];

    /**
     * Holds passport jwt payload that gets built in api.
     * Ref https://write.razorpay.com/doc/about-edge-passport-mCa579K52t.
     * @var array
     */
    protected $passport = [];

    /**
     * Used to access passport related information stored during PreAuthenticate.
     * @var RequestContextV2
     */
    protected $reqCtx;

    /**
     * Holds passport jwt payload from a background job
     *
     * @var string
     */
    protected $passportFromJob;

    /**
     * Used to recognise Bank Lms Requests
     *
     * @var bool
     */
    protected $isBankLms = false;

    /**
     * Used to identify account id passed in body
     * @var string
     */
    protected $accountIdFromBody = null;

    public function __construct($app)
    {
        $this->app = $app;
        $this->reqCtx = $app['request.ctx.v2'];
    }

    public function init()
    {
        $app = $this->app;

        $this->request                     = $app['request'];
        $this->internalAppConfigs          = $app['config']->get('applications');
        $this->internalBasicAuthAppConfigs = $app['config']->get('applications_v2');
        $this->cloud                       = $app['config']->get('app.cloud');
        $this->router                      = $app['router'];
        $this->trace                       = $this->app['trace'];
        $this->repo                        = $this->app['repo'];
        $this->route                       = $this->app['api.route'];
        $this->merchant                    = null;
        $this->device                      = null;
        $this->isAdmin                     = false;
        $this->appAuth                     = false;
        $this->proxy                       = false;
        $this->passport                    = [];
        $this->passportFromJob             = "";
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

        $keyError = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_CHECK_AND_SET_KEY_ID], function () use ($key)
        {
            return $this->checkAndSetKeyId($key);
        });

        if ($keyError !== null)
        {
            return $keyError;
        }

        $this->authCreds->creds[self::SECRET] = $secret;

        $this->authCreds->setPublicKey($key);

        //
        // The following change is temporary.
        //
        $routeNames = ['payment_fetch_multiple', 'payment_fetch_by_id', 'refund_fetch_multiple', 'refund_fetch_by_id'];

        $XRazorpayAccountHeader = $this->request->headers->get(RequestHeader::X_RAZORPAY_ACCOUNT);

        if ((in_array($this->route->getCurrentRouteName(), $routeNames) === true) and
            ($XRazorpayAccountHeader !== null))
        {
            $this->trace->info(
                TraceCode::PAYMENT_FETCH_REQUEST_FOR_LINKED_ACCOUNT,
                [
                    'route_name'                        => $this->route->getCurrentRouteName(),
                    RequestHeader::X_RAZORPAY_ACCOUNT   => $XRazorpayAccountHeader,
                ]
            );
        }

        return $this->checkAndSetAccountId();
    }

    public function checkAndSetKeyId($key)
    {
        $this->checkAndSetCreds($key);

        return $this->authCreds->validateAndSetKeyId($key);
    }

    public function setAuthDetailsUsingPublicKey($publicKey)
    {
        $this->setPublicKey($publicKey);

        $this->authCreds->setPublicKey($publicKey);

        $this->authCreds->validateAndSetKeyId($publicKey);

        $this->setKeyEntityFromKeyId();
    }

    public function setAccountId($accountId)
    {
        $this->authCreds->creds[self::ACCOUNT_ID] = $accountId;
    }

    protected function setKeyEntityFromKeyId()
    {
        $keyId =  $this->authCreds->creds[self::KEY_ID];

        if (empty($keyId) === false)
        {
            $key = $this->repo->key->findNotExpired($keyId);

            if ($key !== null)
            {
                $this->authCreds->setKeyEntity($key);
            }
        }
    }

    /**
     * Determines whether the current request through batch upload
     *
     * @return bool
     */
    public function isBatchFlow(): bool
    {
        return (empty($this->batch) === false);
    }

    public function setBatch(BatchEntity $batch)
    {
        $this->batch = $batch;
    }

    public function setBatchContext(array $batchRequestContext)
    {
        $this->batchRequestContext = $batchRequestContext;
    }

    public function getBatchContext()
    {
        if ($this->isBatchFlow() === true)
        {
            return $this->batchRequestContext;
        }

        return null;
    }

    /**
     * This function checks if it's API key auth or client auth
     * and sets the context and initializes authCreds accordingly
     * The authCreds class holds all the actual credential details.
     *
     * @param string $key
     */
    protected function checkAndSetCreds(string $key): void
    {
        $validPartnerKey = static::isValidPartnerKey($key);

        $this->isPartnerAuth = $validPartnerKey ?? false;

        $authCredsClass = $validPartnerKey ? ClientAuthCreds::class : KeyAuthCreds::class;

        $this->authCreds = new $authCredsClass($this->app, $key);
    }

    public static function isValidPartnerKey(string $key): bool
    {
        $keyRegex = '/^rzp_(test|live)_(partner)_[a-zA-Z0-9]{14}$/';

        return (preg_match($keyRegex, $key, $matches) === 1);
    }

    /**
     * If Account ID was sent, verify and set its value in $this->creds[]
     *
     * @param  string|null      $accountId
     * @return ApiResponse|null
     */
    public function checkAndSetAccountId(string $accountId = null)
    {
        $accountId = $this->request->headers->get(RequestHeader::X_RAZORPAY_ACCOUNT);

        if (empty($accountId) === true)
        {
            $accountId = $this->request->input('account_id');

            // will return null if doesnt exist and doesnt throw error
            $this->accountIdFromBody = $this->request->request->get(self::ACCOUNT_ID);

            if (empty($accountId) === true)
            {
                return null;
            }

            $this->removeRequestKey(self::ACCOUNT_ID);
        }

        if ($this->verifyAccountId($accountId) === false)
        {
            return $this->invalidAccountId($accountId);
        }

        $this->authCreds->creds[self::ACCOUNT_ID] = $accountId;

        $callbackKey = $this->getCallbackKeyWithAccountId($accountId);

        $this->authCreds->setPublicKey($callbackKey);

        return null;
    }

    public function getCallbackKeyWithAccountId(string $accountId): string
    {
        return $this->getPublicKey() . self::PARTNER_CALLBACK_KEY_DELIMITER . Account::getSignedId($accountId);
    }

    /**
     * Verify and set account id in $this->authCreds->creds[] and set callback key
     *
     * @param  string|null      $accountId
     * @return ApiResponse|null
     */
    protected function checkAndSetPartnerExtraInput(string $accountId = null)
    {
        if ($accountId === null)
        {
            return null;
        }

        if ($this->verifyAccountId($accountId) === false)
        {
            return $this->invalidAccountId($accountId);
        }

        $callbackKey = $this->getCallbackKeyWithAccountId($accountId);

        $this->authCreds->creds[self::ACCOUNT_ID] = Account::verifyIdAndSilentlyStripSign($accountId);

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
    public function hasPartnerAuthCallbackKey(): bool
    {
        $key = $this->getKeyForNonBasicAuthTokens();

        $matches = [];

        // Sample token: rzp_test_partner_1DP5mmOlF5G5ag-acc_ACIg2tb8NySnuh
        // Todo: For bc we have [-~] in below regex, to be removed soon after this deploy.
        $keyRegex = '/^(rzp_(test|live)_partner_[a-zA-Z0-9]{14})[-~](acc_[a-zA-Z0-9]{14})$/';

        $validCallbackKey = (preg_match($keyRegex, $key, $matches) === 1);

        if ($validCallbackKey === true)
        {
            $this->partnerAuthCallbackData = [
                self::KEY           => $matches[1],
                self::ACCOUNT_ID    => $matches[3],
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
    public function handlePartnerAuthOnPublicCallback()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $data = $this->partnerAuthCallbackData;

        $key       = $data[self::KEY];
        $accountId = $data[self::ACCOUNT_ID];

       // This is a hacky fix for now to support laravel 9 upgrade as we were getting null pointer exception at L749
       // Unsure currently how it was was earlier with laravel 8
        if ($this->authCreds === null)
        {
            $this->authCreds = new KeyAuthCreds($this->app);
        }

        $this->authCreds->creds[self::KEY]        = $key;
        $this->authCreds->creds[self::ACCOUNT_ID] = Account::verifyIdAndSilentlyStripSign($accountId);

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

        $error = $this->checkAndSetPartnerExtraInput($accountId);

        if ($error !== null)
        {
            return $error;
        };

        $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_MERCHANT, $this->getMerchantId());

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

        $isKeyExisting = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_IS_KEY_EXISTING], function ()
            {
                return $this->authCreds->isKeyExisting();
            });
        if ($isKeyExisting === true)
        {
            $response = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_VERIFY_KEY_NOT_EXPIRED], function () {
                    return $this->authCreds->verifyKeyNotExpired();
                });
            if ($response === true)
            {
                $response = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_VERIFY_SECRET], function () {
                    return $this->authCreds->verifySecret();
                });
            }

            if ($response !== true)
            {
                return $response;
            }

            $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_MERCHANT, $this->getMerchantId(), true);

            $error = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_CHECK_AND_SET_ACCOUNT_SCOPE], function () {
                return $this->checkAndSetAccountScope();
            });
            if ($error !== null)
            {
                return $error;
            }

            // merchant auth request having account id passed in body
            if (! $this->isPartnerAuth() && ! empty($this->accountIdFromBody))
            {
                // this is a temporary log to identify merchants who are using deprecated practice of passing account id in body
                // this log will be removed post identification of the above said merchants
                $this->trace->info(TraceCode::ACCOUNT_ID_PASSED_IN_BODY_FOR_MERCHANT_AUTH,
                    [
                        self::HTTP_CONTENT_TYPE => $this->request->header(self::CONTENT_TYPE), // will return null if doesnt exist and does not throw any error
                        self::PARTNER_ID => $this->getKeyEntity()->getMerchantId(),
                        self::KEY_ID => $this->getPublicKey(),
                        self::ACCOUNT_ID => $this->accountIdFromBody,
                        self::ROUTE => $this->route->getCurrentRouteName(),
                        self::HTTP_METHOD => $this->request->getRealMethod(),
                    ]);

                // do not allow merchant except the whitelisted one to pass account id on body.
                // as it is a non standard approach and we want to avoid any other merchants adopting the same.
                // for whitelisted merchant also only allow account id in body for order_create route only.
                // as it is existing integration and we will not allow same integration for any other route.
                $keyEntity = $this->getKeyEntity();

                if ( $this->route->getCurrentRouteName() != 'order_create' || !(isset($keyEntity) && in_array($keyEntity->getMerchantId(),self::WHITE_LISTED_MIDS_FOR_ACCOUNT_ID_IN_BODY)) ) {
                    throw new Exception\BadRequestException(
                        ErrorCode::BAD_REQUEST_ERROR, null, null,
                        PublicErrorDescription::BAD_REQUEST_ACCOUNT_ID_IN_BODY
                    );
                }

            }

            $checkAndSetPartnerMerchantScope = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_CHECK_AND_SET_PARTNER_MERCHANT_SCOPE], function () {
                return $this->checkAndSetPartnerMerchantScope();
            });
            return $checkAndSetPartnerMerchantScope;
        }
        else
        {
            $verifyInternalAppAsProxy = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_VERIFY_INTERNAL_APP_AS_PROXY], function () {
                return $this->verifyInternalAppAsProxy();
            });

            if ($verifyInternalAppAsProxy === true)
            {
                $this->setDashboardHeaders();

                $this->setProxyTrue();

                Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_SET_ADMIN_AUTH_IF_APPLICABLE], function () {
                    return $this->setAdminAuthIfApplicable();
                });

                $this->setPassportImpersonationClaims(
                    $this->admin ? self::PASSPORT_IMPERSONATION_TYPE_ADMIN_MERCHANT : self::PASSPORT_IMPERSONATION_TYPE_USER_MERCHANT,
                    $this->authCreds->getMerchant()->getId()
                );

                $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_APPLICATION, $this->internalApp, true, ['name' => $this->internalApp]);

                $scope = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_CHECK_AND_SET_ACCOUNT_SCOPE], function () {
                    return $this->checkAndSetAccountScope();
                });
                return $scope;
            }
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

    public function oauthPublicTokenAuth(string $token = null, string $auth = Type::PUBLIC_AUTH)
    {
        $this->setType($auth);

        $this->authCreds = new KeyAuthCreds($this->app, $token);

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
        $this->trace->info(
            TraceCode::AUTH_TYPE_USED, [
                self::AUTH_TYPE     => self::KEYLESS_AUTH,
                self::ENTITY_ID     => $entityId,
                self::ROUTE         => app('request.ctx')->getRoute(),
                self::EDGE_CONSUMER => $this->reqCtx->passport && $this->reqCtx->passport->consumer
                                            ? $this->reqCtx->passport->consumer->id : null
            ]);

        $this->authCreds = new KeyAuthCreds($this->app);

        $this->authCreds->setModeAndDbConnection($mode);
        $this->authCreds->setAndCheckMerchantActivatedForLive($merchant);

        // Sets the key instance if it exists, gets used in forming signature for payment authorize response
        $key = $this->repo->key->getLatestActiveKeyForMerchant($merchant->getId());
        $this->authCreds->setKeyEntity($key);

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

        $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_MERCHANT, $this->getMerchantId());

        return $this->checkAndSetPartnerMerchantScope();
    }

    public function directAuth()
    {
        $key = $this->request->input(self::KEY_ID);

        if (empty($key) === false)
        {
            return $this->publicAuth();
        }

        $this->authCreds = new KeyAuthCreds($this->app);

        $this->setType(Type::DIRECT_AUTH);
    }

    public function appAuth()
    {
        $this->setType(Type::PRIVILEGE_AUTH);

        $this->setAppAuth(true);

        // Requires the presence of Authorization header
        // Fails if both key and secret are null
        // verifies and sets key id, secret, public key and mode
        // Optionally if account header present, adds account id suffix to public key
        $res = $this->setCredentials();

        if ($res !== null)
        {
            return $res;
        }

        // Check key is blank and it's an internal app (via old key-auth flow)
        if (($this->isKeyBlank()) and ($this->verifyInternalApp()))
        {
            $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_APPLICATION, $this->internalApp, true, ['name' => $this->internalApp]);

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

            $res = $this->checkAndSetAccountScope();

            $this->trace->count(Metric::APP_AUTH_SUCCESS_TOTAL, [
                "flow" => "key_auth",
                "app"  => $this->getInternalApp()
            ]);

            return $res;
        }
        // If key is not blank and this is not proxy auth
        // check for new basic auth flow for app auth (using passport)
        elseif ($this->isValidPassportForAppAuth() and
                !$this->isKeyBlank() and !$this->isProxyAuth())
        {
            // setting internal app using applications_v2 config and passport
            $appConfig = $this->internalBasicAuthAppConfigs[$this->reqCtx->passport->consumer->id];
            $this->internalApp = $appConfig['name'];

            $this->trace->info(
                TraceCode::APP_AUTHENTICATION_FROM_JWT_PASSED, ['app'   => $this->getInternalApp()]);

            $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_APPLICATION, $this->getInternalApp(),
                true, ['name' => $this->getInternalApp()]);

            $this->trace->count(Metric::APP_AUTH_SUCCESS_TOTAL, [
                "flow" => "basic_auth",
                "app"  => $this->getInternalApp()
            ]);

            return null;
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

            $this->setPassportImpersonationClaims(
                self::PASSPORT_IMPERSONATION_TYPE_USER_MERCHANT,
                $this->authCreds->getMerchant()->getId()
            );

            $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_APPLICATION, $this->internalApp, true, ['name' => $this->internalApp]);

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

                $this->setPassportConsumerClaims(
                    self::PASSPORT_CONSUMER_TYPE_ADMIN,
                    $this->admin->getId(),
                    true,
                    ['org_id' => $this->adminOrgId]
                );

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

    public function p2pDeviceAuth()
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

        $response = $this->verifyP2pDeviceToken();

        if ($response === true)
        {
            return;
        }

        return $response;
    }

    /**
     * enforce merchant key to be passed in auth header
     * disables keyless authentication
     */
    public function p2pPublicAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        return $this->keyPublicAuth();
    }

    /**
     * Allows requests with public keys to get through.
     * Also allows private key based requests too
     */
    public function publicCallbackAuth()
    {
        $this->setType(Type::PUBLIC_AUTH);

        $key = $this->router->current()->parameter(self::KEY_ID);

        if ($key === null)
        {
            $res = $this->setCredentials();

            if ($res !== null)
                return $res;
        }

        // If key is wrong in formatting or something, send error back
        if ($this->checkAndSetKeyId($key) !== null)
        {
            return $this->authCreds->invalidApiKey();
        }

        // Ref doc comment for $authCreds. These lines must be invoked after
        // checkAndSetKeyId call.
        $this->authCreds->creds[self::SECRET] = null;
        $this->authCreds->setPublicKey($key);

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

        $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_MERCHANT, $this->getMerchantId());
    }

// --------------------- Basic Auths Ends --------------------------------------

// --------------------- Verifiers ---------------------------------------------

    public function verifyAccountId(string & $accountId)
    {
        try
        {
            Account::verifyIdAndSilentlyStripSign($accountId);
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

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $device = $this->repo->device->findByAuthToken($deviceToken);

        $this->device = $device;

        if (($device === null) or
            ($keyEntity->merchant->getId() !== $device->merchant->getId()))
        {
            $this->trace->info(TraceCode::BAD_REQUEST_INVALID_API_SECRET, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
        }
    }

    /**
     * Here we very the device auth for P2p
     *
     * @return boolean/Response
     */
    protected function verifyP2pDeviceToken()
    {
        $merchant = $this->authCreds->getMerchant();

        $deviceToken = $this->authCreds->getSecret();

        if ($deviceToken === '')
        {
            $this->trace->info(
                TraceCode::BAD_REQUEST_API_SECRET_NOT_PROVIDED, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_SECRET_NOT_PROVIDED);
        }

        $device = $this->repo->p2p_device->findByAuthToken($deviceToken);

        $this->device = $device;

        if (($device === null) or
            ($merchant->getId() !== $device->merchant->getId()))
        {
            $this->trace->info(TraceCode::BAD_REQUEST_INVALID_API_SECRET, [self::KEY_ID => $this->getKey()]);

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_SECRET);
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

        if (empty($merchantId) === false)
        {
            $merchant = $this->repo->merchant->find($merchantId);
        }
        else
        {
            $merchant = null;
        }

        $this->authCreds->setMerchant($merchant);

        // If merchant id isn't found, then return false.
        return ($merchant !== null);
    }

    /**
     * validates if the passport is correct for app auth use case
     * @return bool
     */
    protected function isValidPassportForAppAuth()
    {
        // No passport attached to request
        if (!$this->reqCtx->hasPassportJwt) {
            $this->trace->error(TraceCode::NO_PASSPORT_FOUND);
            return false;
        }

        $passport = $this->reqCtx->passport;

        // Can happen when wrong public key used for passport
        if ($passport === null) {
            $this->trace->error(TraceCode::PASSPORT_NOT_SET);
            return false;
        }

        // If identification failed, request should be rejected from Edge
        if (!$passport->identified) {
            $this->trace->error(TraceCode::APP_IDENTIFICATION_FAILED);
            return false;
        }

        // Invalid consumerClaims
        if (!$passport->consumer or !$passport->consumer->id) {
            $this->trace->error(TraceCode::INVALID_APP_PASSPORT_CLAIMS);
            return false;
        }

        $appId = $passport->consumer->id;
        // Invalid consumer type
        if ($passport->consumer->type !== self::PASSPORT_CONSUMER_TYPE_APPLICATION) {
            $this->trace->error(TraceCode::INVALID_APP_PASSPORT_CLAIMS,
                [
                    'consumer_type' => $passport->consumer->type,
                    'app_id' => $appId
                ]);
            return false;
        }

        // No app config present for the given application_id
        if (!array_key_exists($appId, $this->internalBasicAuthAppConfigs)) {
            $this->trace->error(TraceCode::NO_APP_CONFIG, ['app_id' => $appId]);
            return false;
        }

        $config = $this->internalBasicAuthAppConfigs[$appId];
        if (!is_array($config) or !array_key_exists('name', $config) or
            !is_string($config['name']) or empty($config['name'])) {
            $this->trace->error(TraceCode::INVALID_APP_CONFIG, ['app_id' => $appId]);
            return false;
        }

        // If authentication failed, request should be rejected from Edge
        if (!$passport->authenticated) {
            $this->trace->error(TraceCode::APP_AUTHENTICATION_FAILED, ['app' => $config['name']]);
            return false;
        }

        return true;
    }

    /**
     * @depracated Uses old key-auth flow
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

        //
        // If the internal application is 'automation' and the route is proxy, authenticate requests only for
        // white-listed merchants. Reason: Automation services should not have access to routes for all merchants
        // via proxy also.
        //
        if (($this->isAutomationApp() === true) and
            (($this->isProxyAuth() === false) or
            ($this->isAutomationSuiteMid() === false)))
        {
            return false;
        }

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

    public function getAccountId()
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->creds[self::ACCOUNT_ID] = $this->authCreds->creds[AuthCreds::ACCOUNT_ID];
        }

        return $this->creds[self::ACCOUNT_ID];
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
        // If partner credentials are being used with submerchant header, then isPartnerAuth() will return true
        // If partner credentials are being used without submerchant header, (partner credentials used for own behalf)
        // then isPartnerAuth() will return false, we are returing from here since key entity is not defined for ClientAuthCreds class
        if (($this->isPartnerAuth() === true) or (method_exists($this->authCreds, 'getKeyEntity') === false))
        {
            return;
        }

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

    public function getPartnerMerchant()
    {
        $partnerMid = $this->getPartnerMerchantId();

        if (empty($partnerMid) === false)
        {
            return $this->repo->merchant->find($partnerMid);
        }

        return null;
    }

    public function getTokenScopes()
    {
        return $this->tokenScopes;
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

    public function isSettlementsApp()
    {
        return $this->internalApp === 'settlements_service';
    }

    public function isVendorPaymentApp()
    {
        return $this->internalApp === 'vendor_payments';
    }

    public function isMetroApp()
    {
        return $this->internalApp === 'metro';
    }

    public function isXPayrollApp()
    {
        return $this->internalApp === 'xpayroll';
    }

    public function isScroogeApp()
    {
        return $this->internalApp === 'scrooge';
    }

    public function isEzetapApiApp()
    {
        return $this->internalApp === 'ezetap-api';
    }

    public function isPayoutLinkApp()
    {
        return $this->internalApp === 'payout_links';
    }

    public function isAccountsReceivableApp()
    {
        return $this->internalApp === 'accounts_receivable';
    }

    public function isBusinessReportingApp()
    {
        return $this->internalApp === 'business_reporting';
    }

    public function isAccountingIntegrationsApp()
    {
        return $this->internalApp === 'accounting_integrations';
    }

    public function isPayoutService()
    {
        return $this->internalApp === 'payouts_service';
    }

    public function isCareApp()
    {
        return $this->internalApp === 'care';
    }

    public function isMobApp()
    {
        return $this->internalApp === 'master_onboarding';
    }

    public function isDashboardApp()
    {
        /*
         * frontend_graphql is added here because it's a client facing proxy layer similar to dashboard doing today.
         * It's required to set requestOriginProduct.
        */
        return (in_array($this->getInternalApp(), self::DASHBOARD_APPS, true) === true);
    }

    public function isRouteDirectTransferRequest()
    {
        return ($this->app['api.route']->getCurrentRouteName() === 'transfer_create');
    }

    public function isXDashboardApp()
    {
        return (in_array($this->getInternalApp(), self::X_DASHBOARD_APPS, true) === true);
    }

    public function isInternalApp(): bool
    {
        return (($this->isDashboardApp() === true) or
                ($this->isFrontendGraphqlApp() === true) or
                ($this->isVendorPaymentApp() === true) or
                ($this->isMetroApp() === true) or
                ($this->isPayoutLinkApp() === true) or
                ($this->isAccountsReceivableApp() === true) or
                ($this->isBusinessReportingApp() === true) or
                ($this->isCapitalCardsApp() === true) or
                ($this->isMobApp() === true) or
                ($this->isCapitalLOCApp() === true) or
                ($this->isCapitalCollectionsApp() === true) or
                ($this->isCapitalEarlySettlementApp() === true) or
                ($this->isSettlementsApp() === true) or
                ($this->isScroogeApp() === true) or
                ($this->isEzetapApiApp() === true) or
                ($this->isReminderServiceAuth() === true) or
                (($this->isBatchApp() === true) and
                 $this->request->headers->get(RequestHeader::X_Creator_Type) == 'user') or
                (($this->isExpress() === true) and
                  empty($this->request->headers->get(RequestHeader::X_Creator_Type)) === false)
        );
    }

    public function isDebugApp()
    {
        $app = $this->getInternalApp();

        return Route::isDebugApp($app);
    }

    public function isCron()
    {
        return ($this->getInternalApp() === 'cron');
    }

    public function isHosted()
    {
        return ($this->getInternalApp() === 'hosted');
    }

    public function isExpress()
    {
        return ($this->getInternalApp() === 'express');
    }

    public function isFrontendGraphqlApp(): bool
    {
        return ($this->getInternalApp() === 'frontend_graphql');
    }

    public function isSubscriptionsApp()
    {
        return ($this->getInternalApp() === 'subscriptions');
    }

    public function isAutomationApp(): bool
    {
        return ($this->getInternalApp() === 'automation');
    }

    /**
     * Returns true if current key(in case of proxy it is merchant identifier) is
     * one of multiple merchant ids created for automation suite.
     *
     * @return boolean
     */
    public function isAutomationSuiteMid(): bool
    {
        return in_array($this->authCreds->getKey(), Merchant\Account::AUTOMATION_SUITE_MERCHANT_IDS, true);
    }

    public function isBatchApp(): bool
    {
        return ($this->getInternalApp() === 'batch');
    }

    public function isCapitalCardsApp(): bool
    {
        return ($this->getInternalApp() === 'capital_cards_client');
    }
    public function isCapitalLOCApp(): bool
    {
        return ($this->getInternalApp() === 'loc');
    }

    public function isDashboardGuest(): bool
    {
        return ($this->getInternalApp() === 'dashboard_guest');
    }

    public function isCapitalLOSApp(): bool
    {
        return ($this->getInternalApp() === 'los');
    }

    public function isCapitalCollectionsApp(): bool
    {
        return ($this->getInternalApp() === 'capital_collections_client');
    }

    public function isCapitalEarlySettlementApp(): bool
    {
        return ($this->getInternalApp() === 'capital_early_settlements');
    }

    public function isPaymentLinkServiceApp(): bool
    {
        return ($this->getInternalApp() === 'payment_links');
    }

    public function isBankingAccountServiceApp(): bool
    {
        return ($this->getInternalApp() === 'banking_account_service');
    }

    public function isWorkflowsServiceApp(): bool
    {
        return ($this->getInternalApp() === 'workflows');
    }

    public function getOAuthApplicationId()
    {
        return $this->applicationId;
    }

    public function isOptimiserDashboardRequest()
    {
        return ($this->isProxyAuth() === true and
                ((new Feature\Service())->checkFeatureEnabled(Feature\Constants::MERCHANT, $this->getMerchantId(), Feature\Constants::RAAS))['status'] and
                ((new Feature\Service())->checkFeatureEnabled(Feature\Constants::MERCHANT, $this->getMerchantId(), Feature\Constants::ENABLE_SINGLE_RECON))['status']);
    }

    /**
     * Checks if request is coming via a lambda trigger
     *
     * @return boolean
     */
    public function isLambda(): bool
    {
        return ($this->internalApp === 'h2h');
    }

    public function isReminderServiceAuth()
    {
        return ($this->internalApp === 'reminders');
    }

    public function isMerchantDashboardApp(): bool
    {
        return ($this->getInternalApp() === 'merchant_dashboard');
    }

// --------------------- Getters Ends ------------------------------------------

// --------------------- Setters -----------------------------------------------

    public function setMode(string $mode)
    {
        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->authCreds->setMode($mode);

            $mode = $this->authCreds->getMode();
        }

        $this->mode = $mode;

        $this->app['rzp.mode'] = $mode;
    }

    public function setModeAndDbConnection(string $mode)
    {
        $this->setMode($mode);

        \Database\DefaultConnection::set($mode);
    }

    public function setBasicAppAuth(bool $value)
    {
        $this->setAppAuth($value);
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

    public function setTokenScopes(array $tokenScopes)
    {
        $this->tokenScopes = $tokenScopes;
    }

    public function setMerchant($merchant)
    {
        if ($merchant !== null)
        {
            /** @var Org\Entity $org */
            $org = $merchant->org;

            $this->setOrgId($org->getPublicId());

            $this->setOrgType($org->getType());

            // basic auth is scattered across the code in core and services
            // for avoiding duplicate code setting merchant here

            $this->merchant = $merchant;
        }

        $authCreds = $this->authCreds;

        if ((empty($authCreds) === false))
        {
            $this->authCreds->setMerchant($merchant);
        }
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

        $this->setMerchant($merchant);
    }

    public function setBasicType(string $type)
    {
        $this->type = $type;
    }

    protected function setType($type)
    {
        $this->type = $type;
    }

    protected function setAdminTrue()
    {
        $this->isAdmin = true;
    }

    public function setPublicKey($publicKey)
    {
        $this->creds[self::PUBLIC_KEY] = $publicKey;
    }

    protected function setProxyTrue()
    {
        $this->proxy = true;

        $this->setAppAuth(true);
    }

    protected function setAppAuth(bool $value)
    {
        $this->appAuth = $value;
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

    public function isPartnerAuth()
    {
        return $this->isPartnerAuth;
    }

    public function isOAuth(): bool
    {
        return $this->app['request.ctx']->getAuthFlowType() === self::OAUTH;
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

    public function isDirectAuth()
    {
        return ($this->type === Type::DIRECT_AUTH);
    }

    public function isSlackApp()
    {
        return ($this->getAccessTokenId() !== null and
            ((new Feature\Service())->checkFeatureEnabled(Feature\Constants::APPLICATION, $this->getOAuthApplicationId(), Feature\Constants::PUBLIC_SETTERS_VIA_OAUTH))['status']);
    }

    public function isAppleWatchApp(): bool
    {
        $scopes = $this->tokenScopes;

        if (empty($scopes))
        {
            return false;
        }

        if (in_array(OAuthScopes::APPLE_WATCH_READ_WRITE,$scopes,true))
        {
            return true;
        }

        return false;
    }

    public function getSourceChannel()
    {
        if( $this->isSlackApp() === true)
        {
            return Entity::SLACK_APP;
        }

        return null;
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
        $this->authCreds->setPublicKey($key);

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
        if (($this->isPartnerAuth() === true) or ($this->isAccountAuthAllowed() === false))
        {
            return null;
        }

        $account = $this->repo->merchant->find($this->getAccountId());

        $validateAccountForCurrentAuthType = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_VALIDATE_ACCOUNT_FOR_CURRENT_AUTH_TYPE], function () use ($account){
                return ($account === null) or ($this->validateAccountForCurrentAuthType($account) === false);
            });
        if ($validateAccountForCurrentAuthType)
        {
            return $this->invalidAccountId($this->getAccountId());
        }

        $this->authCreds->setMerchant($account);

        // This flow is used in at least 1) Route product, 2) Admin auth flow.
        $this->setPassportImpersonationClaims(
            $this->admin ? self::PASSPORT_IMPERSONATION_TYPE_ADMIN_MERCHANT : self::PASSPORT_IMPERSONATION_TYPE_PARTNER,
            $account->getId()
        );
    }

    /**
     * 1. Check if merchant is marked as a partner
     * 2. Set partner merchant id in the auth context
     * 3. Fetch account_id from header
     * 4. If account_id header is null, set partner merchant as current merchant,
     *      allow access for whitelisted routes,
     *      set attributes to just as they would be in private auth and return
     * 5. Set current merchant as sub-merchant
     * 6. Check sub-merchant activated for live (We don't
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

        if ($this->isPartnerAuthAllowed() === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED);
        }

        $accountId = $this->getAccountId();

        $route = $this->router->currentRouteName();

        // If accountId is empty in partner Auth, then instead of submerchant's behalf, partner should be able to make
        // requests on his own behalf (for whitelisted routes), just like private auth, so we are setting attributes just as they would be
        // in case of private auth
        if (empty($accountId) === true)
        {
            if (in_array($route, Route::$partnerCredentialsWithoutSubmerchantIdWhitelist, true) === true)
            {
                $this->isPartnerAuth = false;

                $this->authCreds->unsetPartnerClient();

                $this->authCreds->unsetPartnerApplicationId();

                return;
            }

            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_PARTNER_ACCOUNT_ID_REQUIRED);
        }
        else
        {
            // Added temporary logs to check if partner trying to access apis by passing account_id in req body
            try
            {
                $this->trace->info(
                    TraceCode::IMPACT_PARTNER_PASSING_ACCOUNT_ID_IN_BODY, ['partner_id' => $this->authCreds->getMerchant()->getId(),
                                                             'accountId'  => $accountId]);
            }
            catch (\Exception $exception)
            {
                $this->trace->info(
                    TraceCode::IMPACT_PARTNER_PASSING_ACCOUNT_ID_IN_BODY_FAILED, ['exception' => $exception,
                                                             'accountId'  => $accountId]);
            }
        }

        $account = $this->repo
                        ->merchant
                        ->find($accountId);

        if (empty($account) === true)
        {
            return $this->invalidAccountId($accountId);
        }

        $this->setPartnerMerchantId($this->authCreds->getMerchant()->getId());

        try
        {
            Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_SET_AND_CHECK_MERCHANT_ACTIVATED_FOR_LIVE], function () use ($account) {
                return $this->authCreds->setAndCheckMerchantActivatedForLive($account);
            });
        }
        catch (LogicException $e)
        {
            return ApiResponse::generateErrorResponse(
                ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_ACTIVATED);
        }

        $merchantId = $this->authCreds->getMerchant()->getId();

        $partnerId = $this->getPartnerMerchantId();

        $isMerchantManagedByPartner = Tracer::inspan(['name' => HyperTrace::BASIC_AUTH_IS_MERCHANT_MANAGED_BY_PARTNER], function () use ($merchantId, $partnerId) {
            return (new Merchant\Core)->isMerchantManagedByPartner($merchantId, $partnerId);
        });
        if ($isMerchantManagedByPartner === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER);
        }

        $applicationId = $this->authCreds->getPartnerApplicationId();

        // $this->applicationId will be set to null if it is not set in authCreds. Also, it defaults to null.
        $this->setOAuthApplicationId($applicationId);

        $this->setPassportImpersonationClaims(self::PASSPORT_IMPERSONATION_TYPE_PARTNER, $account->getId());
    }

    protected function isPartnerAuthAllowed(): bool
    {
        $partnerMerchant = $this->authCreds->getMerchant();
        //
        // $this->merchant needs to have been set, and have been marked
        // as non pure-platform type partner
        //
        if ((empty($partnerMerchant) === true) or
            ($partnerMerchant->isPartner() === false) or
            ($partnerMerchant->isPurePlatformPartner() === true))
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
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_ERROR, null, null,
                PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST);
        }
    }

    protected function invalidApiKey()
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_API_KEY, [self::KEY_ID => $this->getKey()]);

        return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY);
    }

    protected function invalidAccountId(string $accountId)
    {
        $this->trace->info(
            TraceCode::BAD_REQUEST_INVALID_ACCOUNT_HEADER,
            [
                self::AUTH_TYPE  => $this->getAuthType(),
                self::KEY_ID     => $this->authCreds->getKey(),
                self::ACCOUNT_ID => $accountId,
            ]);

        return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID);
    }

    protected function isKeyBlank()
    {
        return ($this->authCreds->creds[AuthCreds::KEY_ID] === '');
    }

    /**
     * @deprecated This function will be removed, please use \RZP\Services\CredcaseSigner.
     *
     * The secret used for signing can be one of the following:
     * 1. Api key secret for key auth
     * 2. OAuth app's client secret for Bearer/PublicOAuthToken auth
     * 3. Dummy partner app's client secret for partner auth
     *
     * @param  $str
     *
     * @param null $publicKey
     * @param string $variant
     * @return string
     *
     * @throws Exception\LogicException
     */
    public function sign($str, $publicKey = null)
    {
        if ($publicKey !== null)
        {
            $secret = $this->getSecretForPublicKey($publicKey);

            if ($secret !== null)
            {
                return hash_hmac(self::HMAC_ALGO, $str, $secret);
            }
        }

        $key = $this->getKeyEntity();

        if (($key === null) and ($this->isPartnerAuth() === false) and ($this->oauthClientId === null))
        {
            throw new Exception\LogicException('Key cannot be null here');
        }

        if (empty($this->oauthClientId) === false)
        {
            $client = (new OAuthClient\Repository)->findOrFailPublic($this->oauthClientId);
        }

        if (empty($client) === true and ($this->isPartnerAuth() === true))
        {
            $client = $this->authCreds->getPartnerClient();
        }

        if (empty($client) === false)
        {
            $secret = $client->getSecret();
        }
        else
        {
        	$secret = $key->getDecryptedSecret();
        }

        return hash_hmac(self::HMAC_ALGO, $str, $secret);
    }

    private function getSecretForPublicKey($publicKey)
    {
        $secret = null;

        $found = (preg_match(self::OAUTH_KEY_REGEX, $publicKey, $matches) === 1);

        if ($found === true)
        {
            $token = (new OAuthServer($this->app['env']))->authenticateWithPublicToken($matches[1]);

            if ((isset($token) === true) and
                (isset($token['client_id']) === true))
            {
                $clientId = $token['client_id'];

                $client = (new OAuthClient\Repository)->findOrFailPublic($clientId);

                $secret = $client->getSecret();

                return $secret;
            }
        }

        $found = (preg_match(self::PARTNER_KEY_REGEX, $publicKey, $matches) === 1);

        if ($found === true)
        {
            $clientId = $matches[3];

            $client = (new OAuthClient\Repository)->findOrFailPublic($clientId);

            $secret = $client->getSecret();

            return $secret;
        }

        $found = (preg_match(self::KEY_REGEX, $publicKey, $matches) === 1);

        if ($found === true)
        {
            $key = $this->repo->key->findNotExpired($matches[2]);

            if (isset($key) === true)
            {
                $secret = $key->getDecryptedSecret();

                return $secret;
            }
        }

        return $secret;
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
            (
                ($this->authCreds->getMerchant()->isMarketplace() === true) or
                ($this->authCreds->getMerchant()->isPartner())
            ))
        {

            $this->trace->info(TraceCode::ACCOUNT_AUTH_ALLOWED,
                [
                    'route'          => $this->route->getCurrentRouteName(),
                    'partner_id'     => $this->authCreds->getMerchant()->getId(),
                    'is_marketplace' => $this->authCreds->getMerchant()->isMarketplace(),
                    'partner_type'   => $this->authCreds->getMerchant()->getPartnerType()
                ]);
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
                return $this->parentAndPartnerCheckForPrivateAuth($account);

            case Type::PRIVILEGE_AUTH:
                return true;

            default:
                return false;
        }
    }

    /**
     * Returns true if account's Parent Id is equal to MerchantId or
     * ( route is whitelisted to accept X-Razorpay-Account and
     * the accountId is referred submerchant of the Partner )
     *
     * @param $account
     *
     * @return bool
     */
    public function parentAndPartnerCheckForPrivateAuth($account)
    {
        $route_name = $this->route->getCurrentRouteName();
        if (($account->getParentId() === $this->authCreds->getMerchant()->getId()))
        {
            $this->trace->info(TraceCode::LINKED_ACCOUNT_USAGE,
                [
                    'route'          => $route_name,
                    'submerchant_id' => $account->getId(),
                    'partner_id'     => $this->authCreds->getMerchant()->getId()
                ]);
            return true;
        }

        $merchantCore = new Merchant\Core;
        if ((in_array($route_name, $this->whitelistRoutesForReferrerPartnerAccess, true) === true) and
            ($merchantCore->canSkipWorkflowToAccessSubmerchantKyc($this->authCreds->getMerchant(), $account) === true))
        {
            $this->trace->info(TraceCode::PARTNER_CONTEXT_SWITCH_TO_SUBMERCHANT,
                               ['route_name'     => $route_name,
                                'submerchant_id' => $account->getId(),
                                'partner_id'     => $this->authCreds->getMerchant()->getId()
                               ]);
            return true;
        }

        return false;
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

    public function setOrgDetails(Org\Entity $org)
    {
        $this->setOrgId($org->getPublicId());

        $this->setOrgType($org->getType());
    }

    /**
     * Setting and validating crossOrgId.
     *
     * @param $crossOrgId
     */
    public function setCrossOrgId(string $crossOrgId = null)
    {
        $validateOrgId = $crossOrgId;

        if (empty($validateOrgId) === false)
        {
            $this->repo->org->isValidOrg(Org\Entity::verifyIdAndStripSign($validateOrgId));
        }

        $this->crossOrgId = $crossOrgId;
    }

    public function getOrgId()
    {
        return $this->orgId;
    }

    /**
     * Getting crossOrgId.
     *
     * @return string|null $crossOrgId
     */
    public function getCrossOrgId()
    {
        return $this->crossOrgId;
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
        if ($this->app->environment('testing') === false && $this->app->environment('testing_docker') === false)
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

    /**
     * Certain access and return contents are controlled by org
     * type, hence setting it in the auth object
     *
     * @param $orgType
     *
     * @return $this
     */
    public function setOrgType($orgType)
    {
        $this->orgType = $orgType;

        return $this;
    }

    public function getOrgType()
    {
        return $this->orgType;
    }

    public function getOrgHostName()
    {
        return $this->orgHostName;
    }

    public function setIdempotencyKeyId(string $idempotencyKeyId)
    {
        $this->idempotencyKeyId = $idempotencyKeyId;

        return $this;
    }

    public function getIdempotencyKeyId()
    {
        return $this->idempotencyKeyId;
    }

    /**
     * @param string $requestOriginProduct
     *
     * @return $this
     */
    public function setRequestOriginProduct(string $requestOriginProduct)
    {
        $this->requestOriginProduct = $requestOriginProduct;

        return $this;
    }

    /**
     * @param bool $isBankLms
     *
     * @return void
     */
    public function setBankLms(bool $isBankLms)
    {
        $this->isBankLms = $isBankLms;
    }

    public function isBankLms(): bool
    {
        return $this->isBankLms;
    }

    public function getRequestOriginProduct(): string
    {
        return $this->requestOriginProduct;
    }

    public function setProduct(string $product)
    {
        $this->product = $product;

        return $this;
    }

    public function getProduct(): string
    {
        return $this->product;
    }

    public function isProductPrimary(): bool
    {
        return ($this->getRequestOriginProduct() === Product::PRIMARY);
    }

    /**
     * Denotes if a request came from banking source or primary dashbaord
     * @return bool
     */
    public function isProductBanking(): bool
    {
        return ($this->getRequestOriginProduct() === Product::BANKING);
    }

    /**
     * Sets $user instance var value by given $userId.
     * Called by OAuth flow. OAuth server response contains the same($userId).
     *
     * @param string $userId
     */
    public function setUserById(string $userId)
    {
        $user = $this->repo->user->findOrFail($userId);

        $this->setUser($user);
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

    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * Verifies and sets user from the headers.
     */
    public function verifyAndSetUser()
    {
        $dashboardHeaders = $this->getDashboardHeaders();

        $userId = $dashboardHeaders['user_id'] ?? null;

        if ($userId === null)
        {
            if ($this->isExpress() === true)
            {
                $userId = $this->getUserIdForRoute();
            }
            else if($this->isEzetapApiApp() === true)
            {
                $mid = $this->authCreds->getKey();

                $userId = $this->getUserIdForRouteFromMerchantUsers($mid);
            }
            else
            {
                $userId = $this->request->headers->get(RequestHeader::X_Creator_Id, null);

                $userType = $this->request->headers->get(RequestHeader::X_Creator_Type, null);

                if ((empty($userType) === false) and
                    ($userType === 'admin'))
                {
                    return;
                }
            }
        }

        if (empty($userId) === false)
        {
            $user = $this->repo->user->findOrFailPublic($userId);

            $this->setUser($user);

            $this->setUserRole($userId);

            $this->setPassportConsumerClaims(self::PASSPORT_CONSUMER_TYPE_USER, $userId, true);

            $userRoles = [];

            // $this->userRole can be string|null
            if (empty($this->userRole) === false)
            {
                $userRoles = [$this->userRole];

                $authzRoles = (new \RZP\Models\RoleAccessPolicyMap\Service())->getAuthzRolesForRoleId($this->userRole);

                $userRoles = array_merge($userRoles, $authzRoles);
            }

            $this->setPassportRoles($userRoles);
        }
    }

    public function setUserRole(string $userId)
    {
        // Fetching MID from authcreds because X-Razorpay-Account will be set as ba merchant
        // When a marketplace account requests on behalf of linked account. so fetching the user
        // mapping via keyId and userId.
        if ($this->isProxyAuth() === true)
        {
            $merchantId = $this->authCreds->creds[self::KEY_ID];
        }

        if (empty($merchantId) === false)
        {
            $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId, $userId);

            if (empty($userMapping) === false)
            {
                $this->userRole = $userMapping->pivot->role;
            }
            else
            {
                $this->userRole = (new UserService)->syncMerchantUserOnProducts($merchantId);
            }
        }
    }

    public function setUserRoleWithUserIdAndMerchantId($merchantId, $userId, $product = Product::PRIMARY)
    {
        if (empty($merchantId) === true || empty($userId) === true)
        {
            return;
        }

        // ToDo:: Once oauth tokens have product level segregation this can be removed and product can be derived from token.
        if ($this->isSlackApp() === true || $this->isAppleWatchApp())
        {
            $product = Product::BANKING;
        }

        $userMapping = $this->repo->merchant->getMerchantUserMapping($merchantId, $userId, null, $product);

        if (empty($userMapping) === false)
        {
            $this->userRole = $userMapping->pivot->role;
        }
    }

    public function isAdminLoggedInAsMerchantOnDashboard()
    {
        $headerValue =  $this->request->header(RequestHeader::X_DASHBOARD_ADMIN_AS_MERCHANT);

        $isAdminLoggedIn = (bool) ($headerValue ?? false);

        if (($isAdminLoggedIn === true) and
            ($this->isDashboardApp() === true))
        {
            return true;
        }

        return false;
    }

    public function getKeyForNonBasicAuthTokens()
    {
        // Check `key_id` first, else fallback to BasicAuth user
        $keyParam = $this->request->input(self::KEY_ID);
        $key      = $keyParam ?? $this->request->getUser();

        // For callback routes, gets the key from route parameter
        $route = $this->router->currentRouteName();
        if ((empty($key) === true) and (in_array($route, Route::$publicCallback, true) === true))
        {
            $key = $this->router->current()->parameter(self::KEY_ID);
        }

        return $key;
    }

    public function removeRequestKey(string $key)
    {
        $this->request->query->remove($key);
        $this->request->request->remove($key);
    }

    /**
     * Check if admin has access to other organisations.
     *
     * @return bool
     *
     */
    public function adminHasCrossOrgAccess()
    {
        return ($this->isAdminAuth() === true) and
               (empty($this->admin) === false) and
               ($this->getAdmin()->org->isCrossOrgAccessEnabled() === true);
    }

    /**
     * Returns the origin type and origin id based on the auth used.
     *
     * If the merchant's credentials are used, ['merchant', $merchantId] is returned.
     * If the partner or the oauth credentials are used, ['application', $oauthApplicationId] is returned.
     *
     * @return array
     */
    public function getOriginDetailsFromAuth(): array
    {
        $originId = $originType = null;

        switch (true)
        {
            //
            // This case covers the following cases -
            //      the bearer auth (pure platform partner flow), and,
            //      the partner auth (aggregator, fully managed partner flow)
            //
            case (empty($this->getOAuthApplicationId()) === false):

                $originType = EntityOrigin\Constants::APPLICATION;
                $originId   = $this->getOAuthApplicationId();
                break;

            //
            // isPublicAuth() returns true even for partner auth when the partner key is used.
            // Hence keep this case at the end.
            // privateAuth is required for S2S payments.
            //
            case ($this->isPublicAuth() === true):
            case ($this->isPrivateAuth() === true):

                $originType = EntityOrigin\Constants::MERCHANT;
                $originId   = $this->getMerchantId();
                break;
        }

        return [$originType, $originId];
    }

    public function getRequestMetricDimensions(): array
    {
        $ctx = app('request.ctx');

        return [
            'mode'           => $ctx->getMode(),
            'route'          => $ctx->getRoute(),
            'auth'           => $ctx->getAuth(),
            'proxy'          => $ctx->getProxy(),
            'bearer'         => empty($ctx->getBearerToken()) === false,
            'auth_flow_type' => $ctx->getAuthFlowType(),
        ];
    }

    /**
     * @return array
     */
    public function getPassport(): array
    {
        return $this->passport;
    }

    /**
     * Sets passport's mode.
     * @param string $mode
     */
    public function setPassportMode(string $mode)
    {
        $this->passport['mode'] = $mode;
    }

    /**
     * Sets passport's credential claims.
     * @param string      $username
     * @param string|null $publicKey
     */
    public function setPassportCredentialClaims(string $username, string $publicKey = null)
    {
        // - credential.username is username from http basic auth.
        // - credential.public_key is for constructing callback urls, and as signer sdk argument. It is same as username for private auth.
        $this->passport['credential'] = ['username' => $username, 'public_key' => $publicKey];
    }

    /**
     * Sets passport's consumer claims.
     * @param string $type
     * @param string $id
     * @param bool   $authenticated
     * @param array  $meta
     */
    public function setPassportConsumerClaims(string $type, string $id, bool $authenticated = false, array $meta = [])
    {
        $this->passport['identified']    = true;
        $this->passport['authenticated'] = $authenticated;
        $this->passport['consumer']      = ['type' => $type, 'id' => $id];

        if (empty($meta) === false)
        {
            $this->passport['consumer']['meta'] = $meta;
        }
    }

    /**
     * Sets passport's oauth claims.
     * @param string $ownerType
     * @param string $ownerId
     * @param string $clientId
     * @param string $appId
     * @param string $env
     */
    public function setPassportOAuthClaims(string $ownerType, string $ownerId, string $clientId, string $appId, string $env)
    {
        $this->passport['identified'] = true;

        $this->passport['oauth'] = [
            'owner_type' => $ownerType,
            'owner_id'   => $ownerId,
            'client_id'  => $clientId,
            'app_id'     => $appId,
            'env'        => $env,
        ];
    }

    /**
     * Sets passport's impersonation claims.
     * @param string $type
     * @param string $consumerId
     */
    public function setPassportImpersonationClaims(string $type, string $consumerId, string $consumerType = self::PASSPORT_CONSUMER_TYPE_MERCHANT)
    {
        $this->passport['impersonation'] = ['type' => $type, 'consumer' => ['id' => $consumerId, 'type' => $consumerType]];
    }

    /**
     * Returns impersonation claims registered on passport.
     * @return array | null
     */
    public function getPassportImpersonationClaims() : array | null
    {
        return $this->getPassport() !== null && isset($this->getPassport()['impersonation']) ? $this->getPassport()['impersonation'] : null;
    }

    /**
     * Sets passport's roles.
     * @param array $roles
     */
    public function setPassportRoles(array $roles)
    {
        $this->passport['roles'] = $roles;
    }

    /**
     * Sets passport's authenticated.
     * @param bool $authenticated
     */
    public function setPassportAuthenticated(bool $authenticated)
    {
        $this->passport['authenticated'] = $authenticated;
    }

    /**
     * Sets passport's domain.
     * @param string $domain
     */
    public function setPassportDomain(string $domain)
    {
        $this->passport['domain'] = $domain;
    }

    /**
     * Returns passport jwt which can be forwarded to upstream request. It is
     * similar to passport received by edge. It is signed by different private
     * key. The upstream is expected to configure both public keys i.e.
     * edge's and api's.
     *
     * @return string
     */
    public function getPassportJwt(string $upstreamHost, int $customExpirySecs = 0): string
    {
        $passportConfig = $this->app['config']->get('passport');

        $issuerId           = $passportConfig['issuer_id'];
        $privateKey         = InMemory::plainText($passportConfig['issuer_private_key']);
        $privateKeyId       = $passportConfig['issuer_private_key_id'];
        $passportExpirySecs = $passportConfig['issuer_passport_expire_secs'];

        // set $customExpirySecs only if more than default.
        // using caution since not to have an extremely short lived token set by mistake.
        $passportExpirySecs = ($customExpirySecs > $passportExpirySecs) ? $customExpirySecs : $passportExpirySecs;

        $interval = 'PT'.$passportExpirySecs.'S';

        $now = time();

        $sysClock = new SystemClock(new DateTimeZone('UTC'));
        $identifier = (empty($this->request) === false) ? $this->request->getId() : substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'),1,20);

        $tokenBuilder = new JWTBuilder(new JoseEncoder(), ChainedFormatter::withUnixTimestampDates());
        $builder = $tokenBuilder
            ->issuedBy($issuerId)
            ->permittedFor($upstreamHost)
            ->identifiedBy($identifier, true)
            ->issuedAt($sysClock->now())
            ->canOnlyBeUsedAfter($sysClock->now())
            ->expiresAt($sysClock->now()->add(new \DateInterval($interval)))
            ->withHeader('kid', $privateKeyId);

        // Appends custom claims.
        foreach ($this->getPassport() as $key => $value)
        {
            $builder->withClaim($key, $value);
        }

        return $builder->getToken(new JWTSigner\Rsa\Sha256, $privateKey)->toString();
    }

    /**
     * Assigns passport token from an async request
     * Thread - https://razorpay.slack.com/archives/C012ZGQQFDJ/p1632727731111400
     */
    public function setPassportFromJob(string $passportToken)
    {
        $this->passportFromJob = $passportToken;
    }

    /**
     * Returns passport token set from an async request
     */
    public function getPassportFromJob()
    {
        return $this->passportFromJob;
    }

    /**
     * Returns user id to be set for express proxy auth routes.
     */
    public function getUserIdForRoute():string
    {
        $type = $this->request->headers->get(RequestHeader::X_Creator_Type, null);

        $id = $this->request->headers->get(RequestHeader::X_Creator_Id, null);

        if ($type === 'merchant')
        {
            try
            {
                return $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($id)[0];
            }
            catch (\Exception $exception)
            {
                return '';
            }
        }

        return $id;
    }

    /**
     * Returns user id to be set for ezetap proxy auth routes.
     */
    public function getUserIdForRouteFromMerchantUsers(string $id):string
    {
        try
        {
            $userId = $this->repo->merchant_user->fetchPrimaryUserIdForMerchantIdAndRole($id)[0];

            if($userId === null)
            {
                return '';
            }

            return $userId;
        }
        catch (\Exception $exception)
        {
            $this->trace->error(TraceCode::ERROR_EXCEPTION, [
                'exception' => $exception
                ]);

            return '';
        }
    }
}
