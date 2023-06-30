<?php

namespace RZP\Http\Edge;

use Illuminate\Http\Request;
use RZP\Http\Route;
use Throwable;
use Razorpay\Trace\Logger;
use Razorpay\Edge\Passport;

use RZP\Trace\TraceCode;
use RZP\Http\RequestContextV2;
use RZP\Http\BasicAuth;
use Illuminate\Routing\Route as IlluminateRoute;

/**
 * Class PostAuthenticate
 *
 * @package RZP\Http\Edge
 *
 * See handle function.
 */
final class PostAuthenticate
{
    /**
     * @var RequestContextV2
     */
    protected $reqCtx;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * This dependency ideally does not belong here and exists here for
     * making assertions during ramp up phase.
     *
     * @var BasicAuth\BasicAuth
     */
    protected $ba;

    /**
     * @var PassportUtil|null
     */
    protected $passportUtil;

    const TYPE_MERCHANT  = "merchant";
    const TYPE_PARTNER   = "partner";
    const DEFAULT_DOMAIN = "razorpay";

    /**
     * @return void
     */
    public function __construct()
    {
        $this->reqCtx = app('request.ctx.v2');
        $this->trace  = app('trace');
        $this->ba     = app('basicauth');
        // set PassportUtil only if passport exists
        $this->passportUtil = empty($this->reqCtx->passport) ? null : new PassportUtil($this->reqCtx->passport);
    }

    /**
     * Responsibilities of this method are described in-line implementation below.
     *
     * @param bool $authenticated Whether Middleware\Authenticate found request to be authenticated.
     * @param Request  $request Current request object
     *
     * @return void
     */
    public function handle(bool $authenticated, Request $request)
    {
        try {
            $funcStartedAt = millitime();
            $this->ensureRequestContextAdditionalAttrs($request);
            $this->reportAuthFlowMismatches($authenticated);
            $this->reportAuthenticationMismatches($authenticated, $request);
            $this->reportImpersonationMismatches($authenticated, $request);
            $this->ensureRequestContextPassport($authenticated);
            $this->reportAuthorizationEnforcementMismatches($authenticated, $request);
            $this->updateAPIPassport();

            $this->trace->histogram(Metric::MIDDLEWARE_POSTAUTH_DURATION_MS, millitime() - $funcStartedAt);
        } catch (Throwable $e) {
            $this->trace->error(TraceCode::POST_AUTHENTICATE_MIDDLEWARE_FAILED, [
                'trace' => $e->getTrace(),
                'message' => $e->getMessage()
            ]);
        }


    }

    /**
     * Checks if current route a public callback route
     *
     * @return bool true if the route is public callback route
     */
    private function isPublicCallbackRoute()
    {
        $currentRoute = app('router')->currentRouteName();

        return (in_array($currentRoute, Route::$publicCallback, true) === true);
    }

    /**
     * Checks if current route is a private route or oauth specific route
     *
     * @return bool true if the current route is a private route or oauth specific route
     */
    private function isAuthenticatedPathRoute(IlluminateRoute $route) : bool
    {
            $currentRoute = $route->getName();

            if ( $currentRoute == NULL ){
                return  false;
            }

            return in_array($currentRoute, Route::OAUTH_SPECIFIC_ROUTES, true) or in_array($currentRoute, Route::$private, true);

    }


    /**
     * Directly updates the API passport with the details from the Edge passport.
     * Also sets the default value if not present in the Edge passport
     *
     * Currently, fields that are directly updates are
     *  - domain
     *
     * @return void
     */
    private function updateAPIPassport()
    {
        $edgePassport = & $this->reqCtx->passport;
        if ( $edgePassport == null ) {
            // set the default domain
            $this->ba->setPassportDomain(self::DEFAULT_DOMAIN);
            return;
        }

        // set the domain info
        $domain = $edgePassport->domain ?: self::DEFAULT_DOMAIN;
        $this->ba->setPassportDomain($domain);
    }

    /**
     * (1A) If request.ctx.v2's passport is not set then set the same.
     * (1B) If request.ctx.v2's passport is set (i.e. from edge service)
     * then asserts that those attributes are same as what Authenticate
     * middleware has evaluated. If assertion fails then log the same and
     * use formers evaluation as correct data.
     *
     * @param bool $authenticated
     *
     * @return void
     */
    private function ensureRequestContextPassport(bool $authenticated)
    {
        $passport = & $this->reqCtx->passport;
        $fromEdge = ($passport !== null);

        // Creates new passport if not set already. Part of case 1A.
        $passport = $passport ?: new Passport\Passport;

        $errors = [];

        $consumerExists = false;
        // consumer is identified at edge even if authentication fails, hence this condition - though authentication is false , we set consumer identified as true in api
        if ( $authenticated === true){
            $consumerExists = ($this->ba->getMerchantId() !== null);
        } else {
            $apiPassport = $this->ba->getPassport();

            //check whether consumer is set in passport
            if (isset($apiPassport['consumer'])) {
                $consumerExists = ($this->ba->getPassport()['consumer']['id'] !== null);
            }
        }

        // For $passport's scalar attributes.
        ensureSameOrOverride($passport->identified, $consumerExists, 'identified', $errors);
        ensureSameOrOverride($passport->authenticated, $authenticated, 'authenticated', $errors);

        // If authenticated false from API, we ignore mode mismatch.
        if ($authenticated === true) {
            ensureSameOrOverride($passport->mode, $this->ba->getMode(), 'mode', $errors);
        }

        $this->ensureRequestContextPassportForDirectAuth($passport, $errors);
        $this->ensureRequestContextPassportForPublicAuth($passport, $errors);
        $this->ensureRequestContextPassportForPrivateAuth($passport, $errors);
        $this->ensureRequestContextPassportForOAuth($passport, $errors);
        $this->ensureRequestContextPassportForPartner($passport, $errors);

        // If $passport was created fresh i.e. not from edge then of course there would be errors(i.e mismatch) :)
        if ($fromEdge and $errors)
        {
            $this->reqCtx->passportAttrsMismatch = true;

            // It reports mismatches only for scenarios which are expected to be handled at edge presently.
            $shouldReport = $this->isPrivateAuth() || $this->isOAuth() || $this->isPublicAuth() || $this->isPartnerAuth();

            if ($shouldReport === true)
            {
                $this->trace->count(Metric::PASSPORT_ATTRS_MISMATCH_TOTAL, $this->ba->getRequestMetricDimensions());

                $dimensions = [
                    'key_id' => $this->ba->getPublicKey()
                ];

                $this->trace->warning(TraceCode::PASSPORT_ATTRS_MISMATCH, [
                        'errors' => $errors,
                        'passport' => $dimensions,
                        'trace_id' => $this->reqCtx->edgeTraceId,
                    ] + $this->ba->getRequestMetricDimensions());
            }
        }
    }

    private function ensureRequestContextPassportForPublicAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isPublicAuth()) {
            return;
        }

        $isConsumerExpected = true;
        ensureSameExistenceOrOverride($passport->consumer, $isConsumerExpected, 'consumer', $errors, new Passport\ConsumerClaims);

        // For $passport->consumer's scalar attributes.
        ensureSameOrOverride($passport->consumer->id, $this->ba->getMerchantId(), 'consumer.id', $errors);
        ensureSameOrOverride($passport->consumer->type, self::TYPE_MERCHANT, 'consumer.type', $errors);
    }

    private function ensureRequestContextPassportForPrivateAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isPrivateAuth()) {
            return;
        }

        // dont rely on apiPassport for now and directly use context from basicauth $this->ba
        //$apiPassport = $this->ba->getPassport();
        //$passportConsumerExists = ($this->ba->getMerchantId() !== null);

        //if (isset($apiPassport['consumer'])) {
        //    $passportConsumerExists = ($apiPassport['consumer']['id'] !== null);
        //}

        // consumer id will be same for merchant auth with and without impersonation
        $consumerId = '';
        // authCreds will be null when key and secret are not passed and request fails with basic auth expected error.
        // verify authCreds is set before calling getKeyEntity()

        $keyEntity = isset($this->ba->authCreds) ? $this->ba->getKeyEntity() : null;
        // keyEntity returns string due to intialization, and will not be set to object if request fails with invalid apikey.
        // make sure keyEntity is an object before calling getMerchantId()
        if (empty($keyEntity) === false && is_object($keyEntity)) {
            $consumerId = $this->ba->getKeyEntity()->getMerchantId();
        }

        $this->checkPassportMismatches($passport, $errors, $consumerId, self::TYPE_MERCHANT, self::TYPE_MERCHANT);
    }

    private function ensureRequestContextPassportForOAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isOAuth()) {
            return;
        }

        $isConsumerExpected = true;
        ensureSameExistenceOrOverride($passport->consumer, $isConsumerExpected, 'consumer', $errors, new Passport\ConsumerClaims);
        ensureSameOrOverride($passport->consumer->id, $this->ba->getPartnerMerchantId(), 'consumer.id', $errors);
        ensureSameOrOverride($passport->consumer->type, self::TYPE_MERCHANT, 'consumer.type', $errors);

        $isOAuthExpected = true;
        ensureSameExistenceOrOverride($passport->oauth, $isOAuthExpected, 'oauth', $errors, new Passport\OAuthClaims);
        ensureSameOrOverride($passport->oauth->ownerId, $this->ba->getMerchantId(), 'oauth.owner_id', $errors);
        ensureSameOrOverride($passport->oauth->ownerType, self::TYPE_MERCHANT, 'oauth.owner_type', $errors);
        ensureSameOrOverride($passport->oauth->clientId, $this->ba->getOAuthClientId(), 'oauth.client_id', $errors);
        ensureSameOrOverride($passport->oauth->appId, $this->ba->getOAuthApplicationId(), 'oauth.app_id', $errors);
    }

    private function ensureRequestContextPassportForPartner(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isPartnerAuth()) {
            return;
        }

        // set consumer id based on impersonation
        $consumerId = $this->ba->getPartnerMerchantId() ?? $this->ba->getMerchantId() ?? '';

        $this->checkPassportMismatches($passport, $errors, $consumerId, self::TYPE_PARTNER, self::TYPE_PARTNER);
    }

    private function ensureRequestContextPassportForDirectAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isDirectAuth()) {
            return;
        }
        $isConsumerExpected = false;
        ensureSameExistenceOrOverride($passport->consumer, $isConsumerExpected, 'consumer', $errors, new Passport\ConsumerClaims);
    }

    private function isPrivateAuth()
    {
        return $this->reqCtx->authType == BasicAuth\Type::PRIVATE_AUTH
            and $this->reqCtx->authFlowType == BasicAuth\BasicAuth::KEY
            and $this->reqCtx->proxy == false;
    }

    private function isOAuth()
    {
        return $this->reqCtx->authFlowType == BasicAuth\BasicAuth::OAUTH;
    }

    private function isPartnerAuth()
    {
        return $this->reqCtx->authFlowType == BasicAuth\BasicAuth::PARTNER;
    }

    private function isDirectAuth()
    {
        return $this->reqCtx->authType == BasicAuth\Type::DIRECT_AUTH;
    }

    private function isPublicAuth()
    {
        return (($this->reqCtx->authType == BasicAuth\Type::PUBLIC_AUTH) &&
            ($this->isPublicCallbackRoute() === false) &&
            ($this->ba->isKeylessPublicAuth() === false));
    }

    /**
     * checks for mismatches in passport attributed between edge passport and api
     * @param Passport\Passport $passport
     * @param array $errors
     * @param string $consumerId
     * @param string $consumerType
     * @param string $impersonationType
     */
    private function checkPassportMismatches(Passport\Passport $passport, array &$errors, string $consumerId,
                                             string $consumerType, string $impersonationType): void
    {
        // set impersonation consumer id
        $impersonationConsumerId = empty($this->ba->getAccountId()) ? null : $this->ba->getMerchantId();
        $credentialPublicKey = $this->ba->getPublicKey();
        $credentialUsername = explode('-', $credentialPublicKey)[0];

        $consumerExists = (empty($consumerId) === false);
        $impersonationConsumerExists = isset($impersonationConsumerId);
        $credentialExists = isset($credentialPublicKey);

        // check if consumer exists in passport
        ensureSameExistenceOrOverride($passport->consumer, $consumerExists, 'consumer', $errors, new Passport\ConsumerClaims);
        if ($consumerExists === true && isset($passport->consumer)) {
            ensureSameOrOverride($passport->consumer->id, $consumerId, 'consumer.id', $errors);
            ensureSameOrOverride($passport->consumer->type, $consumerType, 'consumer.type', $errors);
            // check if credential exists in passport
            ensureSameExistenceOrOverride($passport->credential, $credentialExists, 'credential', $errors, new Passport\CredentialClaims);
            if (isset($passport->credential)) {
                ensureSameOrOverride($passport->credential->username, $credentialUsername, 'credential.username', $errors);
                ensureSameOrOverride($passport->credential->publicKey, $credentialPublicKey, 'credential.publickey', $errors);
            }
            // impersonation block will exist in passport if $impersonationConsumerExists
            // empty impersonation block without impersonation consumer wont exist
            ensureSameExistenceOrOverride($passport->impersonation, $impersonationConsumerExists, 'impersonation', $errors, new Passport\ImpersonationClaims);
            if ($impersonationConsumerExists === true && isset($passport->impersonation)) {
                ensureSameOrOverride($passport->impersonation->type, $impersonationType, 'impersonation.type', $errors);
                if (isset($passport->impersonation->consumer)) {
                    ensureSameOrOverride($passport->impersonation->consumer->id, $impersonationConsumerId, 'impersonation.consumer.id', $errors);
                    ensureSameOrOverride($passport->impersonation->consumer->type, self::TYPE_MERCHANT, 'impersonation.consumer.type', $errors);
                }
            }
        }
    }

    /**
     * (2) In request.ctx.v2 set additional attributes (which does not come
     * from edge service) which api's code uses etc.
     *
     * @param Request $request
     * @return void
     */
    private function ensureRequestContextAdditionalAttrs(Request $request)
    {
        $this->reqCtx->authType = $this->ba->getAuthType();
        $this->reqCtx->proxy    = $this->ba->isProxyAuth();
        $this->reqCtx->authFlowType = app('request.ctx')->getAuthFlowType();
        // TODO: this should be set before passing to Controller, logger should also use this request now
        // instead of generating a new id.
        $this->reqCtx->edgeTraceId = $request->header('X-Razorpay-Request-ID');
    }

    /**
     * Reports any mismatches in AuthZ enforcement result between Edge & API middleware.
     *
     * @param bool $authenticated whether Middleware\Authenticate found request to be authenticated.
     * @param Request $request Current request object
     */
    private function reportAuthorizationEnforcementMismatches(bool $authenticated, Request $request)
    {
        $authzEnforcementResult = $request->headers->get(Constant::AUTHZ_RESULT_HEADER);
        // Enforcer not configured for this request. No need to emit any metrics.
        if ($authzEnforcementResult === NULL) {
            return;
        }

        $identified = ($this->ba->getMerchantId() !== null);

        // API & Enforcer allowed. No mismatch.
        if ($authzEnforcementResult === Constant::AUTHZ_RESULT_ALLOWED and $identified === TRUE)
        {
            return;
        }
        else if ($authzEnforcementResult === Constant::AUTHZ_RESULT_DENIED and $identified === FALSE)
        {
            return;
        }

        $dimensions                            = $this->ba->getRequestMetricDimensions();
        $dimensions['is_api_authenticated']    = $authenticated;
        $dimensions['is_api_identifier']       = $identified;
        $dimensions['edge_enforcement_result'] = $authzEnforcementResult;
        $this->trace->count(Metric::AUTHZ_ENFORCEMENT_MISMATCH_TOTAL, $dimensions);
        // For logs, add merchant_id & key_id as well.
        // Not adding these for prom metrics since that'll increase the cardinality of the metric unnecessarily.
        $dimensions['key_id'] = $this->ba->getPublicKey();
        $dimensions['merchant_id'] = $this->ba->getMerchantId();
        $dimensions['trace_id'] = $this->reqCtx->edgeTraceId;
        $this->trace->warning(TraceCode::EDGE_AUTHORIZATION_MISMATCH, $dimensions);
    }

    /**
     * Reports any mismatches in authentication between edge and API
     * @param bool $authenticated Whether Middleware\Authenticate found request to be authenticated.
     * @param Request $request Current request object
     */
    private function reportAuthenticationMismatches(bool $authenticated, Request $request)
    {

        //for now only log this metrics for private routes.
        if ( !$this->isAuthenticatedPathRoute($request->route()) ){
            return;
        }

        //for now edge only sends this header in case of private auth
        $edgeAuthNResultStr = $request->headers->get(Constant::AUTHN_RESULT_HEADER);
        // no headers from edge so skip
        if ( $edgeAuthNResultStr === NULL ) {
            return;
        }

        $edgeAuthNResult = $edgeAuthNResultStr === Constant::AUTHN_RESULT_ALLOWED;

        // API & Edge results are same. no miss match
        if ( $edgeAuthNResult ==  $authenticated ){
            return;
        }

        $passport = $this->reqCtx->passport;

        if ($passport === NULL){
            return;
        }

        $dimensions                            = $this->ba->getRequestMetricDimensions();
        $dimensions['is_api_authenticated']    = $authenticated;
        $dimensions['is_edge_authenticated']   = $edgeAuthNResult;
        $dimensions['consumer_type']           = $passport->consumer?->type;
        //skipping metrics as of now because api counter metrics is not working
        // For logs, add merchant_id & key_id as well.
        // Not adding these for prom metrics since that'll increase the cardinality of the metric unnecessarily.
        $dimensions['key_id']       = $this->ba->getPublicKey();
        $dimensions['merchant_id']  = $this->ba->getMerchantId();
        $dimensions['trace_id']     = $this->reqCtx->edgeTraceId;
        $this->trace->warning(TraceCode::EDGE_AUTHENTICATION_MISMATCH, $dimensions);
    }

    /**
     * Reports any mismatches in impersonation between edge and API
     *
     * @param bool $authenticated Whether Middleware\Authenticate found request to be authenticated.
     * @param Request $request Current request object
     */
    private function reportImpersonationMismatches(bool $authenticated, Request $request)
    {

        //for now edge only sends this header in case of valid partner auth
        $edgeImpersonationResultStr = $request->headers->get(Constant::IMPERSONATION_RESULT_HEADER);

        // no headers from edge so skip
        if ( $edgeImpersonationResultStr === NULL ) {
            return;
        }

        $edgeImpersonationResult = $edgeImpersonationResultStr === Constant::AUTHN_RESULT_ALLOWED;

        // API & Edge results are same. no mismatch
        if ( $edgeImpersonationResult ==  $authenticated ){
            return;
        }

        $apiImpersonation = $this->ba->getPassportImpersonationClaims();

        $dimensions                            = $this->ba->getRequestMetricDimensions();
        $dimensions['is_api_authenticated']    = $authenticated;
        $dimensions['is_edge_authenticated']   = $edgeImpersonationResult;
        $dimensions['api_impersonation']       = $apiImpersonation;
        //skipping metrics as of now because api counter metrics is not working
        // For logs, add merchant_id & key_id as well.
        // Not adding these for prom metrics since that'll increase the cardinality of the metric unnecessarily.
        $dimensions['key_id']       = $this->ba->getPublicKey();
        $dimensions['merchant_id']  = $this->ba->getMerchantId();
        $dimensions['trace_id']     = $this->reqCtx->edgeTraceId;
        $this->trace->warning(TraceCode::EDGE_IMPERSONATION_MISMATCH, $dimensions);
    }

    /**
     * Reports any mismatches in auth flow between edge and API
     *
     * @param bool $authenticated whether Middleware\Authenticate found request to be authenticated.
     */
    private function reportAuthFlowMismatches(bool $authenticated)
    {
        // just major auth schemes supported at edge alone for now
        if (! ($this->isPrivateAuth() || $this->isPartnerAuth() || $this->isPublicAuth() || $this->isOAuth()))
        {
            return;
        }

        // if edge passport is empty return
        if (empty($this->reqCtx->passport))
        {
            $this->trace->warning(TraceCode::PASSPORT_NOT_FOUND, $this->ba->getRequestMetricDimensions());
            return;
        }

        // if request is not authenticated return
        if ($authenticated === false)
        {
            return;
        }

        $apiAuthFlow  = $this->getAuthTypeAtAPI();

        $edgeAuthFlow = empty($this->passportUtil) ? '' : $this->passportUtil->getAuthTypeFromPassport();

        // no mismatch if API and Edge results are same.
        if ( $apiAuthFlow == $edgeAuthFlow )
        {
            return;
        }

        $dimensions                   = $this->ba->getRequestMetricDimensions();
        $dimensions['api_auth_flow']  = $apiAuthFlow;
        $dimensions['edge_auth_flow'] = $edgeAuthFlow;
        $this->trace->count(Metric::EDGE_AUTHFLOW_MISMATCH_TOTAL, $dimensions);

        // TODO: use passport directly to reduce dependency on ba module
        $dimensions['key_id']         = $this->ba->getPublicKey();
        $dimensions['merchant_id']    = $this->ba->getMerchantId();
        $this->trace->warning(TraceCode::EDGE_AUTHFLOW_MISMATCH, $dimensions);
    }

    /**
     * Gets auth type at API from ba context.
     * later can be extended for other auth schemes as required
     *
     * @return string   ''
     *                  merchant_auth_without_impersonation
     *                  merchant_auth_with_impersonation
     *                  partner_auth_without_impersonation
     *                  partner_auth_with_impersonation
     *                  oauth_without_impersonation
     *                  oauth_with_impersonation
     *                  keyless_auth
     *                  public_merchant_auth_without_impersonation
     *                  public_partner_auth_without_impersonation
     *                  public_partner_auth_with_impersonation
     *                  public_oauth_without_impersonation
     *                  public_oauth_with_impersonation
     */
    private function getAuthTypeAtAPI(): string
    {
        if (empty($this->ba->authCreds)) {
            return '';
        }

        if ($this->ba->isKeylessPublicAuth()) {
            return PassportUtil::KEYLESS_AUTH;
        }

        $prefix = $this->ba->isPublicAuth() ? PassportUtil::PUBLIC_PREFIX : '';
        $authType = $this->ba->isOAuth() ? PassportUtil::OAUTH :
            (($this->ba->authCreds instanceof BasicAuth\KeyAuthCreds) ? self::TYPE_MERCHANT . PassportUtil::AUTH_SUFFIX : self::TYPE_PARTNER . PassportUtil::AUTH_SUFFIX);
        $suffix = empty($this->ba->getAccountId()) ? PassportUtil::WITHOUT_IMPERSONATION : PassportUtil::WITH_IMPERSONATION;

        $authType = $prefix . $authType . $suffix;
        return $authType;
    }
}

/**
 * Check that actual and expected are same and if they are not then actual is
 * overridden with expected. Also the mismatch is put in error.
 *
 * @param mixed  $actual
 * @param mixed  $expected
 * @param string $key
 * @param array  $errors
 *
 * @return void
 */
function ensureSameOrOverride(&$actual, $expected, string $key = '', array &$errors = [])
{
    if ($actual !== $expected)
    {
        $errors[$key] = compact('actual', 'expected');
        $actual       = $expected;
    }
}

/**
 * Checks that actual's existence matches expected existence and if it does not
 * then besides putting in error- set actual value to null(if it should not
 * exists) or default.
 *
 * @param mixed  $actual
 * @param bool   $expectedExists
 * @param string $key
 * @param array  $errors
 * @param mixed  $default
 *
 * @return void
 */
function ensureSameExistenceOrOverride(&$actual, bool $expectedExists, string $key = '', array &$errors = [], $default = null)
{
    $actualExists = ($actual !== null);
    if ($actualExists !== $expectedExists)
    {
        $errors[$key] = compact('actualExists', 'expectedExists');
        if ($actualExists === true)
        {
            $actual = null;
        }
        else
        {
            $actual = $default;
        }
    }
}
