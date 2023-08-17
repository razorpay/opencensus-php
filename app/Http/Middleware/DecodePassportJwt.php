<?php

namespace RZP\Http\Middleware;

use Closure;
use Illuminate\Routing\Router;
use Throwable;
use Razorpay\Trace\Logger;
use Illuminate\Http\Request;
use Razorpay\Edge\Passport\Passport;
use RZP\Http\Edge\Metric;
use RZP\Http\Edge\PassportUtil;

use RZP\Trace\TraceCode;
use RZP\Http\RequestContextV2;

/**
 * Class DecodePassportJwt
 *
 * Handles http request:
 * - Decodes JWT passport from request
 * - Sets the passport in RequestContextV2
 * - Pushes prom metrics
 */
class DecodePassportJwt
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
     * Laravel request class instance
     * @var Request
     */
    protected $request;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @return void
     */
    public function __construct()
    {
        $this->request      = app('request');
        $this->reqCtx       = app('request.ctx.v2');
        $this->passportCfg  = app('config')->get('passport');
        $this->trace        = app('trace');
        $this->router       = app('router');
    }

    /**
     * Initializes request.ctx.v2 against request coming from edge.
     *
     * @param Request $request
     * @param Closure $next
     * @return Closure
     */
    public function handle(Request $request, Closure $next)
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

        // passport should be present for all requests, log otherwise
        if (empty($this->reqCtx->passport)) {
            $this->trace->info(TraceCode::PASSPORT_NOT_SET, [
                'route' => $this->router->currentRouteName()
            ]);
        }
        else {
            $passportUtil = new PassportUtil($this->reqCtx->passport);
            $this->reqCtx->passportUtil = $passportUtil;
            $this->reqCtx->shouldAuthenticateUsingPassport = $passportUtil->shouldAuthenticateUsingPassport($request);
        }

        $this->trace->histogram(Metric::MIDDLEWARE_DECODE_PASSPORT_DURATION_MS, millitime() - $funcStartedAt, ['route' => $this->router->currentRouteName()]);

        return $next($request);
    }
}
