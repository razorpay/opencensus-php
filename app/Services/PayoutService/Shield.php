<?php


namespace RZP\Services\PayoutService;

use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;
use RZP\Trace\TraceCode;

class Shield extends Base
{
    const SHIELD_EVALUATE_PAYOUT_URI= '/payouts/shield/evaluate';

    // payout shield evaluate service name for singleton class
    const PAYOUT_SERVICE_SHIELD_EVALUATE = 'payout_service_shield_evaluate';

    /**
     * @param array $input
     * @return array
     */

    public function evaluatePayoutShieldRulesViaMicroservice(array $input)
    {
        $headers = [Passport::PASSPORT_JWT_V1 => $this->app['basicauth']->getPassportJwt($this->baseUrl)];

        $response = $this->makeRequestAndGetContent(
            $input,
            self::SHIELD_EVALUATE_PAYOUT_URI,
            Requests::POST,
            $headers
        );

        return $response;
    }
}
