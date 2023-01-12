<?php

namespace RZP\Http\Middleware;

use Closure;
use Request;
use ApiResponse;
use RZP\Http\Route;
use RZP\Constants\Mode;
use RZP\Models\Settings;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Http\RequestHeader;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request as HttpRequest;
use RZP\Models\Feature\Constants as Feature;

class MerchantIpFilter
{
    protected $app;
    protected $repo;
    protected $router;

    const DEFAULT_IP_WHITELIST = '*';

    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->ba = $app['basicauth'];

        $this->trace = $app['trace'];
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
            $isValidIp = $this->applyNewWhitelistIfApplicable($request, $merchant);

            if ($isValidIp === true)
            {
                return null;
            }

            if ($isValidIp === false)
            {
                return ApiResponse::unauthorized(ErrorCode::BAD_REQUEST_IP_NOT_WHITELISTED);
            }

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

    //This whitelist is specific to X
    protected function applyNewWhitelistIfApplicable(HttpRequest $request, $merchant)
    {
        $isValidIp = null;

        try
        {
            $service = Route::getServiceMappingForIpWhitelist(optional($request->route())->getName());

            if ($service === null)
            {
                return $isValidIp;
            }

            if ($merchant->isFeatureEnabled(Feature::ENABLE_IP_WHITELIST) === true)
            {
                $requestIp = $request->getClientIp();

                $this->trace->info(TraceCode::NEW_IP_WHITELIST_APPLICABLE,
                    [
                        'service'     => $service,
                        'merchant_id' => $merchant->getId(),
                        'client_ip'   => $requestIp,
                    ]);

                $isValidIp = true;

                try
                {
                    $redisKey = 'ip_config' . '_' . $merchant->getId() . '_' . $service;

                    $whitelistedIps = $this->app['redis']->smembers($redisKey);
                }
                catch (\Throwable $ex)
                {
                    $this->trace->info(TraceCode::IP_CONFIG_REDIS_GET_FAILED,
                        [
                            'service'     => $service,
                            'merchant_id' => $merchant->getId(),
                            'client_ip'   => $requestIp,
                            'exception'   => $ex->getMessage(),
                        ]);

                    $whitelistedIps = json_decode(Settings\Accessor::for($merchant, Settings\Module::IP_WHITELIST_CONFIG)->get($service), true);
                }

                if (in_array(self::DEFAULT_IP_WHITELIST, $whitelistedIps, true) === true)
                {
                    $this->trace->info(TraceCode::MERCHANT_DEFAULT_IP_WHITELIST_APPLIED,
                        [
                            'service'         => $service,
                            'merchant_id'     => $merchant->getId(),
                            'client_ip'       => $requestIp,
                            'whitelisted_ips' => $whitelistedIps,
                        ]);

                    return $isValidIp;
                }

                if ((empty($whitelistedIps) === true) or
                    (in_array($requestIp, $whitelistedIps, true) === false))
                {
                    $this->trace->info(TraceCode::REQUEST_FAILED_FROM_NON_WHITELIST_IP,
                        [
                            'service'         => $service,
                            'merchant_id'     => $merchant->getId(),
                            'client_ip'       => $requestIp,
                            'whitelisted_ips' => $whitelistedIps,
                        ]);

                    $isValidIp = false;
                }
            }
        }
        catch (\Throwable $ex)
        {
            $this->trace->error(TraceCode::MERCHANT_IP_X_FILTER_FAILED,
                [
                    'service'     => $service,
                    'merchant_id' => $merchant->getId(),
                    'client_ip'   => $requestIp,
                    'exception'   => $ex->getMessage(),
                ]);

            throw $ex;
        }

        return $isValidIp;
    }
}
