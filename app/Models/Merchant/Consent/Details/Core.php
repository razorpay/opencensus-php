<?php

namespace RZP\Models\Merchant\Consent\Details;


use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Merchant\Consent\Constants;

class Core extends Base\Core
{

    /**
     * @param $input
     *
     * @return mixed
     * @throws \Throwable
     */
    public function createConsentDetails($input)
    {
        $this->trace->info(TraceCode::CREATE_MERCHANT_CONSENT_DETAILS,
                           [
                               Constants::INPUT => $input
                           ]);

        return $this->repo->transactionOnLiveAndTest(function() use ($input) {

            $details = new Entity();

            $details->generateId();

            $details->build($input);

            $this->repo->merchant_consent_details->saveOrFail($details);

            return $details;
        });
    }
}
