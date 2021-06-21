<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Models\Base;

class Service extends Base\Service
{
    public function handleOnboardingEscalationsCron($input)
    {
        $timeBound = $input[Constants::TIME_BOUND] ?? false;

        (new Core)->triggerPaymentEscalations($timeBound);
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
