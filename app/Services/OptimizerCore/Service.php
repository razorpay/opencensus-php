<?php

namespace RZP\Services\OptimizerCore;

use App;
use RZP\Http\Request\Requests;
use RZP\Trace\TraceCode;

class Service
{

    protected $app;
    const FETCH_MERCHANT_GATEWAY_DATA              = 'fetch_merchant_gateway_data';
    const PATH                                  = 'path';
    const OPTIMIZER_PROVIDER_GATEWAY_DATA_CACHE_PREFIX = 'PROVIDER_GATEWAY_DATA:';
    const OPTIMIZER_PROVIDER_GATEWAY_DATA_CACHE_VALIDITY_SECONDS   = 900; // 5 minutes

    const PARAMS = [
        self::FETCH_MERCHANT_GATEWAY_DATA  =>   [
            self::PATH   => '/v1/merchant_gateway_data/',
        ],
    ];

    public function __construct($app = null)
    {
        if ($app === null)
        {
            $app = App::getFacadeRoot();
        }

        $this->app = $app;
    }


    /**
     * fetchMerchantGatewayData
     * Gets EMI details for merchant gateway related information of banks for cc, dc etc (HDFC, ICICI .. etc)
     * from optimizer providers for example: payu, billdesk etc.
     *
     * @param $merchantID
     *
     * @return array ["emi_plans" => [], "emi_options" => [], "debit_emi_providers"=> [], "emi_types"=>[]]
     */
    public function fetchMerchantGatewayData($merchantID): array
    {

        try
        {
            $cacheResponse = $this->getProviderDataFromCache($merchantID);

            if (empty($cacheResponse) === false)
            {
                $this->app['trace']->info(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_AFFORDABILITY_CACHE_RESPONSE);

                return $cacheResponse;
            }

            $params = self::PARAMS[self::FETCH_MERCHANT_GATEWAY_DATA];

            $response = $this->app['optimizer_core_service_client']->sendRequest($params[self::PATH].$merchantID, array(), Requests::GET);

            $this->app['trace']->info(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_API_RESPONSE);

            if ($response !== null && $response['emi_options'] !== null && $response['emi_plans'] !== null)
            {
                $this->cacheProviderData($merchantID, $response);

                $this->app['trace']->info(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_AFFORDABILITY_CACHE_UPDATE);
            }

            return $response;
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_FETCH_ERROR,
                [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTraceAsString(),
                ]
            );

        }

        return [];
    }


    private function cacheProviderData($merchantID, $response): void
    {

        try
        {
            $this->app['cache']->put(
                $this->getProviderDataCacheKey($merchantID),
                $response,
                self::OPTIMIZER_PROVIDER_GATEWAY_DATA_CACHE_VALIDITY_SECONDS);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_RESPONSE_CACHE_CREATE_FAILED,
                [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTraceAsString(),
                ]
            );
        }
    }

    private function getProviderDataFromCache($merchantID)
    {
        try
        {
            $cachedKey = $this->getProviderDataCacheKey($merchantID);
            return $this->app['cache']->get($cachedKey);
        }
        catch (\Throwable $e)
        {
            $this->app['trace']->error(TraceCode::OPTIMIZER_PROVIDER_GATEWAY_RESPONSE_CACHE_FETCH_FAILED,
                [
                    'type'    => get_class($e),
                    'message' => $e->getMessage(),
                    'code'    => $e->getCode(),
                    'trace'   => $e->getTraceAsString(),
                ]
            );
        }
    }

    private function getProviderDataCacheKey($merchantID): string
    {
        $cachedParams = [];
        $cachedParams['merchant_id'] = $merchantID;

        return self::OPTIMIZER_PROVIDER_GATEWAY_DATA_CACHE_PREFIX . (md5(json_encode($cachedParams)));
    }
}
