<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Trace\TraceCode;

class ProcessStuckPayouts extends Base
{
    const PAYOUT_PROCESS_STUCK_PAYOUTS_REQUEST_URI = '/payouts/process_stuck_payouts';

    // queued payout service name for singleton class
    const PAYOUT_SERVICE_PROCESS_STUCK_PAYOUTS = 'payout_service_process_stuck_payouts';

    /**
     * @param array  $input
     *
     * @return array
     */
    public function dispatchStuckPayouts(array $input): array
    {
        $this->trace->info(TraceCode::PAYOUT_PROCESS_STUCK_PAYOUTS_REQUEST,
            [
                'input' => $input,
            ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::PAYOUT_PROCESS_STUCK_PAYOUTS_REQUEST_URI,
            Requests::POST
        );

        return $response;
    }
}
