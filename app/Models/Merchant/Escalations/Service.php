<?php


namespace RZP\Models\Merchant\Escalations;

use RZP\Constants\Mode;
use RZP\Models\Base;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Trace\TraceCode;

class Service extends Base\Service
{
    public function handleOnboardingEscalationsCron($input)
    {
        $timeBound = $input[Constants::TIME_BOUND] ?? false;
        $core      = (new Core);

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
    }

    public function handleOnboardingCrons($input)
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
    }

    public function handleNoDocOnboardingEscalationsCron($input)
    {
        $timeBound = $input[Constants::TIME_BOUND] ?? false;
        $core      = (new Core);

        $experimentResult = $this->app->razorx->getTreatment(UniqueIdEntity::generateUniqueId(),
            RazorxTreatment::SKIP_OLD_XPRESS_ONBOARDING_ESCALATION,
            Mode::LIVE);

        $isOldXpressEscalationSkipped = ( $experimentResult === 'on' ) ? true : false;

        if($isOldXpressEscalationSkipped === true)
        {
            try
            {
                $core->handleNoDocGmvLimitBreach($timeBound);
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                    'type'  => 'new_no_doc_escalations',
                    'error' => $e->getMessage()
                ]);
            }
        }
        else
        {
            try
            {
                $core->handleNoDocLimitBreach();
            }
            catch (\Exception $e)
            {
                $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                    'type'  => 'no_doc_escalations',
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
