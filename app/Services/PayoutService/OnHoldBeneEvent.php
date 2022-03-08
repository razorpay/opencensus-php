<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class OnHoldBeneEvent extends Base
{
    const BENE_EVENT_PAYOUT_SERVICE_URI = '/payouts/bene_bank_status_update';

    // bene event update for on hold service name for singleton class
    const PAYOUT_SERVICE_BENE_EVENT_UPDATE = 'payout_service_bene_event_update';

    /**
     * @param array  $input
     *
     */
    public function processBeneEventUpdateViaMicroservice(array $input)
    {
        $this->trace->info(TraceCode::ON_HOLD_BENE_EVENT_UPDATE_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::BENE_EVENT_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::ON_HOLD_BENE_EVENT_UPDATE_VIA_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);
    }
}
