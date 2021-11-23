<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Exception;
use RZP\Error\Error;
use RZP\Http\RequestHeader;
use RZP\Models\Payout;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Base\PublicEntity;

class Get extends Base
{
    const GET_PAYOUT_BY_ID_SERVICE_URI = '/payouts/';

    // payout get service name for singleton class
    const PAYOUT_SERVICE_GET = 'payout_service_get';

    /**
     * @param array $input
     * @param string $merchantId
     * @return array
     */
    public function GetPayoutByIdViaMicroservice(string $id, string $merchantId)
    {
        $this->trace->info(TraceCode::PAYOUT_GET_REQUEST_FROM_MICROSERVICE,
            [
                'id'          => $id,
                'merchant_id' => $merchantId
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent([],
            self::GET_PAYOUT_BY_ID_SERVICE_URI . $id,
            Requests::GET,
            $headers
        );

        return $response;
    }
}
