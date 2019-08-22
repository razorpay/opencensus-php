<?php

namespace RZP\Http\Middleware;

use Closure;
use Request;
use ApiResponse;
use RZP\Constants\Mode;
use RZP\Error\ErrorCode;
use RZP\Http\RequestHeader;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request as HttpRequest;

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

        if ($this->ba->isStrictPrivateAuth() === true)
        {
            $ret = $this->authenticateIpForPrivateAuth($request);
        }
        else if ($this->ba->isProxyAuth() === true)
        {
            $ret = $this->authenticateIpForProxyAuth($request);
        }

        if ($ret !== null)
        {
            return $ret;
        }

        return $next($request);
    }

    /**
     * @param HttpRequest $request
     *
     * @return |null
     */
    protected function authenticateIpForPrivateAuth(HttpRequest $request)
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
            (in_array($requestIp, $whitelistedIps,true)))
        {
            return null;
        }

        return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_ACCESS_DENIED);
    }

    /**
     * @param HttpRequest $request
     *
     * @return |null
     */
    protected function authenticateIpForProxyAuth(HttpRequest $request)
    {
        $merchant = $this->ba->getMerchant();

        $mode = $this->ba->getMode();

        if ($mode === MODE::LIVE)
        {
            $whitelistedIps = $merchant->getMerchantDashboardWhitelistedIpsLive();
        }
        else
        {
            $whitelistedIps = $merchant->getMerchantDashboardWhitelistedIpsTest();
        }

        if (empty($whitelistedIps) === true)
        {
            return null;
        }

        $requestIp = $this->fetchClientIpForDashboardRequest($request);

        if(($requestIp === null) or
           (in_array($requestIp, $whitelistedIps,true)))
        {
            return null;
        }

        return ApiResponse::unauthorized(
           ErrorCode::BAD_REQUEST_DASHBOARD_IP_NOT_WHITELISTED);
    }


    /**
     * @param HttpRequest $request
     *
     * @return mixed
     */
    protected function fetchClientIpForDashboardRequest(HttpRequest $request)
    {
        $clientIp = $request->headers->get(RequestHeader::X_DASHBOARD_IP);

        return $clientIp;
    }

}
