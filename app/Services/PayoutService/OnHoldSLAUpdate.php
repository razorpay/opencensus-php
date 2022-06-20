<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class OnHoldSLAUpdate extends Base
{
    const ON_HOLD_SLA_UPDATE_PAYOUT_SERVICE_URI = '/merchant/update_on_hold_slas';

    const PAYOUT_SERVICE_ON_HOLD_SLA_UPDATE = 'payout_service_on_hold_sla_update';

    /**
     * @param array  $input
     *
     */
    public function onHoldSLAUpdateToMicroservice(array $input)
    {
        $this->trace->info(TraceCode::ON_HOLD_SLA_UPDATE_TO_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $response = $this->makeRequestAndGetContent(
            $input,
            self::ON_HOLD_SLA_UPDATE_PAYOUT_SERVICE_URI,
            Requests::POST
        );

        $this->trace->info(
            TraceCode::ON_HOLD_SLA_UPDATE_TO_MICROSERVICE_RESPONSE,
            [
                'payouts service response' => $response,
            ]);
    }
}
