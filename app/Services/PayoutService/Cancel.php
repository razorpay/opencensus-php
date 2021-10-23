<?php

namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Models\Payout;
use RZP\Trace\TraceCode;

class Cancel extends Base
{
    const CANCEL_PAYOUT_SERVICE_URI = '/payouts/cancel_payout/';

    // payout cancel service name for singleton class
    const PAYOUT_SERVICE_CANCEL = 'payout_service_cancel';

    /**
     * @param string      $payoutId
     * @param string|null $remarks
     *
     * @return array
     */
    public function cancelPayoutViaMicroservice(string $payoutId, string $remarks = null)
    {
        $input = [
            Payout\Entity::REMARKS => $remarks
        ];

        $this->trace->info(TraceCode::PAYOUT_CANCEL_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        return $this->makeRequestAndGetContent(
            $input,
            self::CANCEL_PAYOUT_SERVICE_URI . $payoutId,
            Requests::POST,
            $headers
        );
    }
}
