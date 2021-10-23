<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Trace\TraceCode;

class QueuedInitiate extends Base
{
    const PAYOUT_QUEUED_INITIATE_REQUEST_URI = '/payouts/balance_update_event';

    // queued payout service name for singleton class
    const PAYOUT_SERVICE_QUEUED_INITIATE = 'payout_service_queued_initiate';

    /**
     * @param array  $input
     *
     * @return array
     */
    public function dispatchQueuedPayoutBalanceIdToMicroservice(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_QUEUED_INITIATE_REQUEST_VIA_MICROSERVICE_REQUEST,
                           [
                               'input' => $input,
                           ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::PAYOUT_QUEUED_INITIATE_REQUEST_URI,
            Requests::POST
        );

        return $response;
    }
}
