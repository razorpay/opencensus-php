<?php

namespace RZP\Http\Edge;

use Throwable;
use Razorpay\Trace\Logger;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Kid;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;
use RZP\Http\RequestContextV2;

/**
 * Class PreAuthenticate
 *
 * @package RZP\Http\Edge
 *
 * Eventually this class should be the first middleware. For now it is after
 * Middleware/Throttler (see first line of Middleware/Authenticate).
 */
final class PreAuthenticate
{
    /**
     * @var RequestContextV2
     */
    protected $reqCtx;

    /**
     * @var array Refer config/passport.php
     */
    protected $passportCfg;

    /**
     * @var Logger
     */
    protected $trace;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->reqCtx      = app('request.ctx.v2');
        $this->passportCfg = app('config')->get('passport');
        $this->trace       = app('trace');
    }

    /**
     * Initializes request.ctx.v2 against request coming from edge.
     *
     * @param Request $request
     *
     * @return void
     */
    public function handle(Request $request)
    {
        $funcStartedAt = millitime();

        //
        // If jwt exists resolves passport and puts in request.ctx.v2.
        // In case of any parsing failures context will not have passport set
        // which is fine for now but in eventual state will return appropriate
        // error.
        //
        $jwt = $request->headers->get(Passport::PASSPORT_JWT_V1);

        if (isset($jwt) === true)
        {
            $this->reqCtx->hasPassportJwt = true;

            try
            {
                Passport::init($this->passportCfg['jwks_host'], \storage_path('passport'));
                $this->reqCtx->passport = Passport::fromToken($jwt);
            }
            catch (Throwable $e)
            {
                $this->trace->count(Metric::PASSPORT_JWT_PARSE_FAILED_TOTAL);
                $this->trace->traceException($e, Logger::ERROR, TraceCode::PASSPORT_JWT_PARSE_FAILED, compact('jwt'));
            }
        }

        $this->trace->histogram(Metric::MIDDLEWARE_PREAUTH_DURATION_MS, millitime() - $funcStartedAt);
    }
}
