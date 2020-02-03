<?php

namespace RZP\Models\Merchant\AutoKyc;

use RZP\Models\Base;
use RZP\Diag\EventCode;
use RZP\Models\Merchant\Detail\Constants;

class Events extends Base\Core
{

    public function sendServiceVerifierEvents(array $data)
    {
        $eventAttribute = [
            Constants::RESPONSE_TIME => $data[Constants::RESPONSE_TIME] ?? null,
            Constants::STATUS_CODE   => $data[Constants::STATUS_CODE] ?? null,
            Constants::DOCUMENT_TYPE => $data[Constants::DOCUMENT_TYPE] ?? '',
        ];
        $this->app['diag']->trackOnboardingEvent(EventCode::KYC_VERIFIER_SERVICE_RESPONSE_TIME,
                                                 $this->merchant,
                                                 null,
                                                 $eventAttribute);
    }

}
