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

class StatusReasonMap extends Base
{

    const FETCH_PAYOUTS_STATUS_REASON_MAP_BASE_URI = '/payouts/payouts_status_reason_map';

    const PAYOUT_SERVICE_STATUS_REASON_MAP = 'payout_service_status_reason_map';

    /**
     * @return array
     */
    public function GetPayoutStatusReasonMapViaMicroService()
    {
        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent([],
            self::FETCH_PAYOUTS_STATUS_REASON_MAP_BASE_URI,
            Requests::GET,
            $headers
        );

        return $response;
    }

}
