<?php

namespace RZP\Services\PayoutService;

use Requests;
use Razorpay\Edge\Passport\Passport;

use RZP\Trace\TraceCode;

class Cancel extends Base
{
    const CANCEL_PAYOUT_SERVICE_URI = '/payouts/cancel_payout/';

    // payout cancel service name for singleton class
    const PAYOUT_SERVICE_CANCEL = 'payout_service_cancel';

    /**
     * @param array  $input
     * @param string $merchantId
     * @param string $payoutId
     *
     * @return array
     */
    public function cancelPayoutViaMicroservice(array $input, string $merchantId, string $payoutId)
    {
        $this->trace->info(TraceCode::PAYOUT_CANCEL_VIA_MICROSERVICE_REQUEST,
            [
                'input' => $input,
            ]);

        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            self::CANCEL_PAYOUT_SERVICE_URI . $payoutId,
            Requests::POST,
            $headers
        );

        return $response;
    }
}
