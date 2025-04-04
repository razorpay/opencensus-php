<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class PayoutsDualWriteFailureProcessingCron extends Base
{
    const DUAL_WRITE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI = '/cron/payouts_dual_write_failure_processing';

    const PAYOUTS_DUAL_WRITE_FAILURE_PROCESSING_CRON = 'payouts_dual_write_failure_processing_cron';

    const COUNT = 'count';

    const DAYS = 'days';

    /**
     * @param array  $input
     *
     */
    public function triggerDualWriteFailureProcessingViaMicroservice(array $input)
    {
        $response = $this->makeRequestAndGetContent(
            [],
            self::DUAL_WRITE_FAILURE_PROCESSING_CRON_PAYOUT_SERVICE_URI,
            Requests::POST
        );
    }
}
