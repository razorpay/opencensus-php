<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class PayoutsUpdateFailureProcessingCron extends Base
{
    const UPDATE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI = '/payouts/cron/update_failure_processing';

    const PAYOUTS_UPDATE_FAILURE_PROCESSING_CRON = 'payouts_update_failure_processing_cron';

    const COUNT = 'count';

    const DAYS = 'days';

    /**
     * @param array  $input
     *
     */
    public function triggerUpdateFailureProcessingViaMicroservice(array $input)
    {
        $request = [
            self::COUNT => $input[self::COUNT],
            self::DAYS => $input[self::DAYS],
        ];

        $response = $this->makeRequestAndGetContent(
            $request,
            self::UPDATE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::PAYOUTS_SERVICE_UPDATE_FAILURE_PROCESSING_CRON_RESPONSE,
            [
                'response' => $response,
            ]);
    }
}
