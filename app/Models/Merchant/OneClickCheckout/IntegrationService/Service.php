<?php

namespace RZP\Models\Merchant\OneClickCheckout\IntegrationService;

use App;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;

class Service extends Base\Service
{
    const CLEAR_MERCHANT_CONFIG_FROM_CACHE_ROUTE = 'v1/magic/merchant/config/refresh';

    public function clearMerchantConfigsFromCache($input): array
    {
        $url = self::CLEAR_MERCHANT_CONFIG_FROM_CACHE_ROUTE;

        return $this->app['integration_service_client']->sendRequest($url, Requests::POST, $input);
    }
}
