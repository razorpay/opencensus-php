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
            $core->sendL1NotSubmittedNotifications($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendL1NotSubmittedNotification',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->sendL1NotSubmittedIn1HourNotifications($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendL1NotSubmittedNotificationIn1Hr',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->sendL2BankDetailsNotSubmittedNotifications($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendL2BankDetailsNotSubmittedNotification',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->sendL2AadharNotSubmittedNotifications($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendL2AadharNotSubmittedNotifications',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->sendOnboardingVerifyEmailNotification($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendOnboardingVerifyEmailNotification',
                'error' => $e->getMessage()
            ]);
        }

        try
        {
            $core->sendNotificationsToInstantlyActivatedButNotTransactedMerchants($input);
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type' => 'sendNotificationsToInstantlyActivatedButNotTransactedMerchants', 'error' => $e->getMessage()
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
    }

    public function fetchOnboardingEscalations()
    {
        return (new Core)->fetchLatestEscalationForMerchant($this->merchant);
    }

    public function handleSendNotificationCron($input)
    {
        $core = (new Core);

        try
        {
            $core->sendSignupStartedNotification();
        }
        catch (\Exception $e)
        {
            $this->trace->info(TraceCode::SEND_NOTIFICATION_ATTEMPT_FAILED, [
                'type'  => 'sendSignupStartedNotification',
                'error' => $e->getMessage()
            ]);
        }
    }
}
