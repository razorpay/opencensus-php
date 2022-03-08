<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class OnHoldCron extends Base
{
    const ON_HOLD_CRON_PAYOUT_SERVICE_URI = '/payouts/on_hold/process';

    const PAYOUT_SERVICE_ON_HOLD_CRON = 'payout_service_on_hold_cron';

    /**
     * @param array  $input
     *
     */
    public function sendOnHoldCronViaMicroservice()
    {
        $this->trace->info(TraceCode::ON_HOLD_CRON_VIA_MICROSERVICE_REQUEST);

        $response = $this->makeRequestAndGetContent(
            [],
            self::ON_HOLD_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::ON_HOLD_CRON_VIA_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);
    }
}
