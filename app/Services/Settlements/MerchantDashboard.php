<?php

namespace RZP\Services\Settlements;

use RZP\Exception\RuntimeException;

/**
 * class for handling all requests from merchant dashboard
 */
class MerchantDashboard extends Base
{
    //********************* All endpoints for merchant dashboard are configured here ***************************//
    const MERCHANT_DASHBOARD_MERCHANT_CONFIG_GET   = '/twirp/rzp.settlements.merchant_config.v1.MerchantConfigService/GetConfigForDashboard';

    /**
     * Merchant Config Service Get for Merchant dashboard
     * @param array  $input
     * @return array
     * @throws RuntimeException
     * @throws \Throwable
     */
    public function merchantDashboardConfigGet(array $input) : array
    {
        return $this->makeRequest(self::MERCHANT_DASHBOARD_MERCHANT_CONFIG_GET, $input, self::SERVICE_MERCHANT_DASHBOARD);
    }
}
