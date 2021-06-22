<?php

namespace RZP\Http\Edge;

use Illuminate\Http\Request;
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
     * @var BasicAuth
     */
    protected $ba;

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

        $this->trace->histogram(Metric::MIDDLEWARE_POSTAUTH_DURATION_MS, millitime() - $funcStartedAt);
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

        // For $passport's scalar attributes.
        ensureSameOrOverride($passport->identified, $authenticated, 'identified', $errors);
        ensureSameOrOverride($passport->authenticated, $authenticated, 'authenticated', $errors);
        ensureSameOrOverride($passport->mode, $this->ba->getMode(), 'mode', $errors);

        // For $passport->consumer existence.
        $consumerExists = ($this->ba->getMerchantId() !== null);
        ensureSameExistenceOrOverride($passport->consumer, $consumerExists, 'consumer', $errors, new Passport\ConsumerClaims);
        if ($consumerExists === true)
        {
            // For $passport->consumer's scalar attributes.
            ensureSameOrOverride($passport->consumer->id, $this->ba->getMerchantId(), 'consumer.id', $errors);
            ensureSameOrOverride($passport->consumer->type, 'merchant', 'consumer.type', $errors);
        }

        // If $passport was created fresh i.e. not from edge then of course there would be errors(i.e mismatch) :)
        if ($fromEdge and $errors)
        {
            $this->reqCtx->passportAttrsMismatch = true;

            // It reports mismatches only for scenarios which are expected to be handled at edge presently.
            $shouldReport = (($this->reqCtx->authFlowType == BasicAuth\BasicAuth::KEY)
                && ($this->reqCtx->authType == BasicAuth\Type::PRIVATE_AUTH)
                && ($this->reqCtx->proxy == false));

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
                ]);
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

        // API & Enforcer allowed. No mismatch.
        if ($authzEnforcementResult === Constant::AUTHZ_RESULT_ALLOWED and $authenticated === TRUE) {
            return;
        }

        // API & Enforcer denied. No mismatch.
        if ($authzEnforcementResult === Constant::AUTHZ_RESULT_DENIED and $authenticated === FALSE) {
            return;
        }

        $dimensions                            = $this->ba->getRequestMetricDimensions();
        $dimensions['is_api_authenticated']    = $authenticated;
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
