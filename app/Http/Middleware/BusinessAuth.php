<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Foundation\Application;

use ApiResponse;
use Illuminate\Routing\Router;
use RZP\Http\OAuth;
use RZP\Http\Route;
use RZP\Error\ErrorCode;
use RZP\Models\Feature;
use RZP\Trace\TraceCode;
use RZP\Http\FeatureAccess;
use RZP\Http\BasicAuth\BasicAuth;
use RZP\Exception\BadRequestException;

use RZP\Models\Merchant;
use RZP\Base\RepositoryManager;

/*
 * This middleware contains checks which are not actual authentication, but checks which should be performed once standard authentication is done
 * these checks are mostly business logic or service level checks
 * even though the name has Auth in it, it is not related to authentication and hence not owned by spine-edge
 * This middleware is separated from Authenticate middleware to differentiate standard authentication (done by Edge/Api-gateway)
 */
class BusinessAuth
{
    const SSL_CERT_HEADER = 'X-Forwarded-Tls-Client-Cert';

    const OAUTH = 'oauth';
    const KEY   = 'key';

    /**
     *  Routes which are allowed to pass X-Razorpay-Account on proxy auth
     * @var array
     */
    public array $whitelistRoutesForReferrerPartnerAccess = [
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
        'merchant_edit_pre_signup_details',
        'merchant_bmc_response_save',
        'merchant_bmc_response_fetch',
        'merchant_consents_save',
        'merchant_identity_verification',
        'merchant_process_verification_details',
        'merchant_website_section_page_load_v2',
        'merchant_get_l2_dynamic_configs',
        'merchant_policy_section_publish_v2',
        'merchant_website_section_save',
        'merchant_website_section_fetch',
        'merchant_website_section_page_load',
    ];

    /**
     * Application instance
     *
     * @var Application
     */
    protected Application $app;

    /**
     * @var BasicAuth
     */
    protected mixed $ba;

    /**
     * @var OAuth
     */
    protected OAuth $oauth;

    /**
     * @var Router
     */
    protected $router;

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
     * @var Merchant\Core
     */
    protected $merchantCore;

    /**
     * Create a new filter instance.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $this->app['basicauth'];

        $this->router = $this->app['router'];

        $this->trace = $this->app['trace'];

        $this->repo   = $this->app['repo'];

        $this->oauth = new OAuth();

        $this->merchantCore = new Merchant\Core();
    }

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure $next
     *
     * @return mixed
     * @throws BadRequestException
     */
    public function handle($request, Closure $next)
    {
        try {
            $route = $this->router->currentRouteName();

            $authFlowType = $this->app['request.ctx']->getAuthFlowType();
            // perform additional or custom checks which are business logics

            $ret = null;

            // do additional oauth checks, includes both oath with and without impersonation flows
            if ($authFlowType === self::OAUTH) {
                $ret = $this->doAdditionalOauthChecks();
            }

            $accountId = $this->ba->getAccountId();
            // this check will be performed for merchant_auth_with_impersonation flow on private_auth, proxy_auth and app_auth currently
            // other auth flows like device auth will also have auth flow type as key
            // but, it will be taken care by isAccountAuthAllowed check in later stages
            if ($authFlowType === self::KEY && empty($accountId) === false) {
                $ret = $this->doAdditionalBasicAuthChecks($route, $accountId);
            }

            // Post process after authentication completes
            $ret = (new FeatureAccess)->verifyFeatureAccess($ret);

            // white listing org and merchants based on features
            if ($ret === null) {
                $ret = (new FeatureAccess)->verifyOrgAndMerchantFeatureAccess();
            }

            // null value indicates failure flow : do not validate further if previous validation failed
            if ($ret === null) {
                $ret = (new FeatureAccess)->verifyOrgLevelFeatureAccess();
            }
        } catch (\Throwable $e) {
            $this->trace->error(TraceCode::BUSINESS_AUTH_MIDDLEWARE_FAILED, [
                'trace' => $e->getTrace(),
                'message' => $e->getMessage()
            ]);
        }

        if ($ret === null) {
            $ret = $this->verifyTlsCertWhitelisted($request, $route);
        }

        // Non-null value indicates failure flow
        if ($ret !== null) {
            return $ret;
        }

        // allow the request to proceed
        return $next($request);
    }

    /**
     * do any additional checks related to oauth here which will not be owned by Edge post API decomp and needs to be implemented by business logic.
     *
     * @return ApiResponse|null
     */
    private function doAdditionalOauthChecks()
    {
        $partnerMerchant = $this->ba->getPartnerMerchant();

        // this check might be removed when oauth with impersonation is implemented at edge
        // this check is currently only ported to keep the behaviour same and seperated
        // more info https://razorpay.slack.com/archives/C012ZGQQFDJ/p1687169588886159
        if ((empty($this->ba->getAccountId()) === false) &&
            $partnerMerchant->isFeatureEnabled(Feature\Constants::AGGREGATOR_OAUTH_CLIENT) === false)
        {
            return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_PARTNER_AUTH_NOT_ALLOWED);
        }

        // this check is not required anymore and rx team will be working on removing it
        // this will we removed once the rx slack app is sunset for now just keep it as it is
        // more info https://razorpay.slack.com/archives/C012ZGQQFDJ/p1687176914633099
        $isRazorpayXExclusiveRoute = $this->oauth->isBankingRoute();
        $isApplicationAllowedForBankingRoutes = (new Feature\Service())->checkFeatureEnabled(Feature\Constants::APPLICATION,
            $this->ba->getOAuthApplicationId(),
            Feature\Constants::RAZORPAYX_FLOWS_VIA_OAUTH)['status'];

        if ($isRazorpayXExclusiveRoute === true && $isApplicationAllowedForBankingRoutes === false)
        {
            return ApiResponse::unauthorizedOauthAccessToRazorpayX();
        }
    }

    /**
     * do any additional checks related to basic auth here which will not be owned by Edge post API decomp and needs to be implemented by business logic.
     *
     * @param string $route
     * @param string $accountId
     *
     * @return null|mixed
     */
    private function doAdditionalBasicAuthChecks(string $route, string $accountId)
    {
        // check if account scope is already set, (i.e) if ba merchant id is equal to passed account id
        if ($this->ba->getMerchantId() === $accountId) {
            return null;
        }

        // set account scope if account auth is allowed and can skip workflow to access submerchant Kyc
        if (! $this->ba->isAccountAuthAllowed()) {
            return null;
        }

        // ignore account scope if request is following new app auth authenticated by edge passport
        if ($this->ba->isAppAuthAuthenticatedWithPassport()) {
            return null;
        }

        // account cannot be null here, as it will be validated by edge if passport auth flow
        // or validated by checkAndSetAccountScope if basic auth flow
        $account = $this->repo->merchant->find($accountId);

        // this check is required for merchant_auth_with_impersonation on private_auth, proxy_auth and app_auth currently
        if (! $this->canSkipWorkflowToAccessSubmerchantKyc($route, $account)) {
            return $this->ba->invalidAccountId($accountId);
        }

        $this->ba->authCreds->setMerchant($account);

        // This flow is used in at least 1) Route product, 2) Admin auth flow.
        $this->ba->setPassportImpersonationClaims(
            $this->ba->isAdminAuth() ? BasicAuth::PASSPORT_IMPERSONATION_TYPE_ADMIN_MERCHANT : BasicAuth::PASSPORT_IMPERSONATION_TYPE_PARTNER,
            $account->getId()
        );
    }

    /**
     * canSkipWorkflowToAccessSubmerchantKyc
     *
     * @param string $route
     * @param Merchant\Entity $account
     * @return bool
     */
    private function canSkipWorkflowToAccessSubmerchantKyc(string $route, Merchant\Entity $account): bool
    {
        // https://razorpay.slack.com/archives/C012ZGQQFDJ/p1687252415363649
        if ((in_array($route, $this->whitelistRoutesForReferrerPartnerAccess, true) === true) and
            ($this->merchantCore->canSkipWorkflowToAccessSubmerchantKyc($this->ba->authCreds->getMerchant(), $account) === true))
        {
            $this->trace->info(TraceCode::PARTNER_CONTEXT_SWITCH_TO_SUBMERCHANT, [
                    'route_name'     => $route,
                    'submerchant_id' => $account->getId(),
                    'partner_id'     => $this->ba->authCreds->getMerchant()->getId()
                ]);

            return true;
        }

        return false;
    }

    /**
     * verifyTlsCertWhitelisted
     *
     * @throws BadRequestException
     */
    private function verifyTlsCertWhitelisted($request, $route)
    {
        if (in_array($route, Route::$tlsRoutes) === false)
        {
            return null;
        }

        $tlsRouteConfig = $this->app['api.route']->getTLSConfig();

        $whiteListedDomainsString = $tlsRouteConfig[$route];

        $whiteListedDomains = explode (",", $whiteListedDomainsString);

        if (in_array('*', $whiteListedDomains) === true)
        {
            return null;
        }

        if ($request->hasHeader(self::SSL_CERT_HEADER) === false)
        {
            app()->trace->info(
                TraceCode::SSL_HEADER_MISSING,
                []
            );

            throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
        }

        $certsString = $request->header(self::SSL_CERT_HEADER);

        $certsArray = explode(',', $certsString);

        foreach($certsArray as $cert)
        {
            $cert = urldecode($cert);

            $start = "-----BEGIN CERTIFICATE-----\n";

            $end = "\n-----END CERTIFICATE-----";

            $cert = $start . $cert . $end;

            $certDetails = openssl_x509_parse($cert);

            if ($certDetails !== false)
            {
                $certCN = $certDetails["subject"]["CN"];

                if (in_array($certCN, $whiteListedDomains) === true)
                {
                    return null;
                }
            }
        }

        app()->trace->info(
            TraceCode::SSL_CERT_VALIDATION_FAILED,
            []
        );

        throw new BadRequestException(ErrorCode::BAD_REQUEST_UNAUTHORIZED);
    }
}
