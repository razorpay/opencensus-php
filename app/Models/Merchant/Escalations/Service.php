<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Models\Base;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function handleOnboardingEscalationsCron($input)
    {
        $timeBound = $input[Constants::TIME_BOUND] ?? false;
        $core      = (new Core);

        try
        {
            $core->handleMtuCouponApply();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'mtu_coupon_apply',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->triggerPaymentEscalations($timeBound);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'PaymentEscalations',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->pushTransactionDetailsToSegmentCron();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'pushTransactionDetailsToSegmentCron',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->pushWebAttributionDetailsToSegmentCron();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'pushWebAttributionDetailsToSegmentCron',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->handleNoDocLimitBreach();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'handleNoDocLimitBreach',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
