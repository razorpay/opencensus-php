<?php

namespace RZP\Http\Middleware;

use Closure;
use Request;
use ApiResponse;
use RZP\Exception;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Foundation\Application;

class MerchantIpFilter
{
    protected $app;
    protected $repo;
    protected $router;

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(HttpRequest $request, Closure $next)
    {
        $ret = null;

        if ($this->ba->isPrivateAuth() === true and $this->ba->isProxyAuth() === false)
        {
            $ret = $this->authenticateIp($request);
        }

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    protected function authenticateIp(HttpRequest $request)
    {
        $requestIp = $request->getClientIp();

        $merchant = $this->ba->getMerchant();

        $mode = $this->ba->getMode();

        if ($mode === MODE::LIVE)
        {
            $whitelistedIps = $merchant->getWhitelistedIpsLive();
        }
        else
        {
            $whitelistedIps = $merchant->getWhitelistedIpsTest();
        }

        if ((empty($whitelistedIps) === true) or
            (in_array($requestIp, $whitelistedIps, true)))
        {
            return null;
        }

        return ApiResponse::unauthorized(
            ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

}
