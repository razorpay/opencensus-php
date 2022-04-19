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

    const CONSUMER_TYPE_MERCHANT = "merchant";
    const PRINCIPAL_TYPE_PARTNER = "partner";
    const DEFAULT_DOMAIN         = "razorpay";

    /**
     * @return void
     */
    public function __construct()
    {
        $this->reqCtx = app('request.ctx.v2');
        $this->trace  = app('trace');
        $this->ba     = app('basicauth');
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
        $funcStartedAt = millitime();

        $this->ensureRequestContextAdditionalAttrs($request);
        $this->ensureRequestContextPassport($authenticated);
        $this->reportAuthorizationEnforcementMismatches($authenticated, $request);
        $this->updateAPIPassport();

        $this->trace->histogram(Metric::MIDDLEWARE_POSTAUTH_DURATION_MS, millitime() - $funcStartedAt);
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
            $shouldReport = $this->isPrivateAuth() or $this->isOAuth() or $this->isPublicAuth();

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
        ensureSameOrOverride($passport->consumer->type, self::CONSUMER_TYPE_MERCHANT, 'consumer.type', $errors);
    }

    private function ensureRequestContextPassportForPrivateAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isPrivateAuth()) {
            return;
        }
        // checks whether consumer is set in passport
        $apiPassport = $this->ba->getPassport();
        $passportConsumerExists = ($this->ba->getMerchantId() !== null);

        if (isset($apiPassport['consumer'])) {
            $passportConsumerExists = ($apiPassport['consumer']['id'] !== null);
        }

        $consumerExists = ($this->ba->getMerchantId() !== null);
        ensureSameExistenceOrOverride($passport->consumer, $passportConsumerExists, 'consumer', $errors, new Passport\ConsumerClaims);
        if ($consumerExists === true) {
            // For $passport->consumer's scalar attributes.
            ensureSameOrOverride($passport->consumer->id, $this->ba->getMerchantId(), 'consumer.id', $errors);
            ensureSameOrOverride($passport->consumer->type, self::CONSUMER_TYPE_MERCHANT, 'consumer.type', $errors);
        }
    }

    private function ensureRequestContextPassportForOAuth(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isOAuth()) {
            return;
        }

        $isConsumerExpected = true;
        ensureSameExistenceOrOverride($passport->consumer, $isConsumerExpected, 'consumer', $errors, new Passport\ConsumerClaims);
        ensureSameOrOverride($passport->consumer->id, $this->ba->getPartnerMerchantId(), 'consumer.id', $errors);
        ensureSameOrOverride($passport->consumer->type, self::CONSUMER_TYPE_MERCHANT, 'consumer.type', $errors);

        $isOAuthExpected = true;
        ensureSameExistenceOrOverride($passport->oauth, $isOAuthExpected, 'oauth', $errors, new Passport\OAuthClaims);
        ensureSameOrOverride($passport->oauth->ownerId, $this->ba->getMerchantId(), 'oauth.owner_id', $errors);
        ensureSameOrOverride($passport->oauth->ownerType, self::CONSUMER_TYPE_MERCHANT, 'oauth.owner_type', $errors);
        ensureSameOrOverride($passport->oauth->clientId, $this->ba->getOAuthClientId(), 'oauth.client_id', $errors);
        ensureSameOrOverride($passport->oauth->appId, $this->ba->getOAuthApplicationId(), 'oauth.app_id', $errors);
    }

    private function ensureRequestContextPassportForPartner(Passport\Passport $passport, array &$errors)
    {
        if (!$this->isPartnerAuth()) {
            return;
        }

        $isConsumerExpected = true;
        ensureSameExistenceOrOverride($passport->consumer, $isConsumerExpected, 'consumer', $errors, new Passport\ConsumerClaims);
        ensureSameOrOverride($passport->consumer->id, $this->ba->getPartnerMerchantId(), 'consumer.id', $errors);
        ensureSameOrOverride($passport->consumer->type, self::CONSUMER_TYPE_MERCHANT, 'consumer.type', $errors);

        $isPartnerAuthExpected = true;
        ensureSameExistenceOrOverride($passport->impersonation, $isPartnerAuthExpected, 'impersonation', $errors, new Passport\ImpersonationClaims);

        ensureSameExistenceOrOverride($passport->impersonation->consumer, $isPartnerAuthExpected, 'impersonation.consumer', $errors, new Passport\ConsumerClaims);
        ensureSameOrOverride($passport->impersonation->consumer->id, $this->ba->getMerchantId(), 'impersonation.consumer.id', $errors);
        ensureSameOrOverride($passport->impersonation->consumer->type, self::CONSUMER_TYPE_MERCHANT, 'impersonation.consumer.type', $errors);

        ensureSameOrOverride($passport->impersonation->type, self::PRINCIPAL_TYPE_PARTNER, 'impersonation.type', $errors);
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
     * @param bool $authenticated Whether Middleware\Authenticate found request to be authenticated.
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
