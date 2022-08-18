<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class PayoutsCreateFailureProcessingCron extends Base
{
    const CREATE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI = '/payouts/cron/create_failure_processing';

    const PAYOUTS_CREATE_FAILURE_PROCESSING_CRON = 'payouts_create_failure_processing_cron';

    const COUNT = 'count';

    const DAYS = 'days';

    /**
     * @param array  $input
     *
     */
    public function triggerCreateFailureProcessingViaMicroservice(array $input)
    {
        $request = [
            self::COUNT => $input[self::COUNT],
            self::DAYS => $input[self::DAYS],
        ];

        $response = $this->makeRequestAndGetContent(
            $request,
            self::CREATE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::PAYOUTS_SERVICE_CREATE_FAILURE_PROCESSING_CRON_RESPONSE,
            [
                'response' => $response,
            ]);
    }
}
