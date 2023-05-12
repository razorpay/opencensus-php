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

            $this->trace->count(Metric::PAYMENT_ESCALATION_SUCCESS_TOTAL);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'PaymentEscalations',
                'error' => $e->getMessage()
            ]);

            $this->trace->count(Metric::PAYMENT_ESCALATION_FAIL_TOTAL);
        }
    }

    public function handleBankingOrgOnboardingEscalationsCron($input)
    {
        $core      = (new Core);

        try
        {
            $core->handlePaymentEscalationsForBankingOrg();

            $this->trace->count(Metric::BANKING_ORG_PAYMENT_ESCALATION_SUCCESS_TOTAL);
        }
        catch (\Throwable $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'BankingOrgPaymentEscalations',
                'error' => $e->getMessage()
            ]);

            $this->trace->count(Metric::BANKING_ORG_PAYMENT_ESCALATION_FAIL_TOTAL);
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

        try
        {
            $core->handleNoDocGmvLimitBreach($timeBound);

            $this->trace->count(Metric::NEW_XPRESS_ESCALATIONS_SUCCESS_TOTAL);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'xpress_escalations',
                'error' => $e->getMessage()
            ]);

            $this->trace->count(Metric::NEW_XPRESS_ESCALATIONS_FAIL_TOTAL);
        }
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
