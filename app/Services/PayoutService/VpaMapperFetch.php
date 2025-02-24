<?php

namespace RZP\Services\PayoutService;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Trace\TraceCode;
use RZP\Models\FundAccount;
use RZP\Http\Request\Requests;
use Razorpay\Edge\Passport\Passport;
use RZP\Error\PublicErrorDescription;

class VpaMapperFetch extends Base
{
    const MAPPED_VPA_PS_URI    = '/payouts/mapped_vpa/%s';

    //singleton class
    const MAPPED_VPA_FETCH = 'mapped_vpa_fetch';

    /**
     * @param string $mobileNumber
     * @return array
     */

    public function fetchMappedVpaViaMicroservice(string $mobileNumber): array {
        $startTime = millitime();

        $response = $this->makeRequestAndGetContent(
            [],
            sprintf(self::MAPPED_VPA_PS_URI, $mobileNumber),
            Requests::GET
        );

        $this->trace->histogram(
            \RZP\Constants\Metric::FETCH_MAPPED_VPA_FROM_PS_TIME_TAKEN,
            millitime() - $startTime);

        $this->trace->info(TraceCode::MAPPED_VPA_RESPONSE_FROM_PS,
            [
                'response' => $response,
                'mobile_number' => $mobileNumber
            ]);

        return $response;
    }
}
