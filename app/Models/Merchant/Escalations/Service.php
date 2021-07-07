<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function handleOnboardingEscalationsCron($input)
    {
        $timeBound = $input[Constants::TIME_BOUND] ?? false;

        try
        {
            (new Core)->handleMtuSegmentEvent();
        }
        catch(\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'segment_mtu',
                'error' => $e->getMessage()
            ]);
        }

        (new Core)->triggerPaymentEscalations($timeBound);
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
