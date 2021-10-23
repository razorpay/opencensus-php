<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;

use RZP\Trace\TraceCode;

class Retry extends Base
{
    const RETRY_PAYOUT_SERVICE_URI = '/payouts/retry';

    // payout service retry name for singleton class
    const PAYOUT_SERVICE_RETRY = 'payout_service_retry';

    /**
     * @param array  $input
     *
     * @return array
     */
    public function retryPayoutViaMicroservice(array $input)
    {
        $this->trace->info(TraceCode::PAYOUT_RETRY_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        return $this->makeRequestAndGetContent(
            $input,
            self::RETRY_PAYOUT_SERVICE_URI,
            Requests::POST
        );
    }
}
