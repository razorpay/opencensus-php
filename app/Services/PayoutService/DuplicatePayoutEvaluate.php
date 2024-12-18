<?php


namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;
use RZP\Trace\TraceCode;

class DuplicatePayoutEvaluate extends Base
{
    const DUPLICATE_PAYOUT_EVALUATE_URI = '/payouts/duplicate_payout_evaluate';

    //singleton class
    const DUPLICATE_PAYOUT_EVALUATE = 'duplicate_payout_evaluate';

    /**
     * @param array $input
     * @return array
     */

    public function duplicatePayoutEvaluateViaMicroservice(array $input): array
    {
        $startTime = millitime();

        $response = $this->makeRequestAndGetContent(
            $input,
            self::DUPLICATE_PAYOUT_EVALUATE_URI,
            Requests::POST
        );

        $this->trace->histogram(
            \RZP\Constants\Metric::DUPLICATE_PAYOUT_EVAlUATE_PS_CALL_DURATION,
            millitime() - $startTime);

        return $response;
    }
}
