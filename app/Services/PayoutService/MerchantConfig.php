<?php

namespace RZP\Services\PayoutService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

class MerchantConfig extends Base
{
    const UPDATE_MERCHANT_FEATURE_PAYOUT_SERVICE = '/merchant/update_merchant_feature_cache';

    // update merchant config attributes singleton class
    const PAYOUT_SERVICE_MERCHANT_CONFIG = 'payout_service_merchant_config';

    /**
     * @param array  $input
     *
     */
    public function updateMerchantFeatureCacheInPayoutMicroservice(array $input)
    {
        $this->trace->info(TraceCode::UPDATE_MERCHANT_FEATURE_IN_PAYOUT_SERVICE_CACHE_REQUEST,
            [
                'input' => $input,
            ]);


        $response = $this->makeRequestAndGetContent(
            $input,
            self::UPDATE_MERCHANT_FEATURE_PAYOUT_SERVICE,
            Requests::PATCH
        );

        $this->trace->info(
            TraceCode::UPDATE_MERCHANT_FEATURE_IN_PAYOUT_SERVICE_CACHE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }
}
