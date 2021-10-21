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
            $core->handleMtuSegmentEvent();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'segment_mtu',
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
            $core->sendNotifications($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::ESCALATION_ATTEMPT_FAILED, [
                'type'  => 'sendNotifications',
                'error' => $e->getMessage()
            ]);
        }
        try
        {
            $core->sendOnboardingVerifyEmailNotification($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_ONBOARDING_VERFIY_EMAIL_NOTIFICATION_FAILED, [
                'type'  => 'sendOnboardingVerifyEmailNotification',
                'error' => $e->getMessage()
            ]);
        }
        try
        {
            $core->sendNotificationsToCouponCodeEligibleMerchant($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::COUPON_CODE_ELIGIBLE_MERCHANT_NOT_MTU_NOTIFICATION_FAILED, [
                'type' => 'sendCouponCodeEligibleMerchantNotMTUNotification', 'error' => $e->getMessage()
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
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }
}
