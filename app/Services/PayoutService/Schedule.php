<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class Schedule extends Base
{
    const SCHEDULE_PAYOUT_SERVICE_URI = '/payouts/scheduled/process';

    // payout schedule service name for singleton class
    const PAYOUT_SERVICE_SCHEDULE = 'payout_service_schedule';

    /**
     * @param array  $input
     *
     * @return array
     */
    public function processSchedulePayoutViaMicroservice(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_SCHEDULED_PROCESS_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::SCHEDULE_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        return $response;
    }
}
