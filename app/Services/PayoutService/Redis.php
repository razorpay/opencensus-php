<?php

namespace RZP\Services\PayoutService;

use RZP\Trace\TraceCode;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

class Redis extends Base
{
    const UPDATE_REDIS_PAYOUTS_SERVICE_URI = '/cron/redis_key_set';

    // payout service redis singleton class
    const PAYOUT_SERVICE_REDIS = 'payout_service_redis';

    /**
     * @param array  $input
     *
     */
    public function payoutsMicroserviceRedisKeySet(array $input)
    {
        $this->trace->info(TraceCode::PAYOUTS_MICROSERVICE_REDIS_KEY_SET_REQUEST,
            [
                'input' => $input,
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            self::UPDATE_REDIS_PAYOUTS_SERVICE_URI,
            Requests::POST,
            $headers
        );

        $this->trace->info(
            TraceCode::PAYOUTS_MICROSERVICE_REDIS_KEY_SET_RESPONSE,
            [
                'payouts service response' => $response,
            ]);

        return $response;
    }
}
