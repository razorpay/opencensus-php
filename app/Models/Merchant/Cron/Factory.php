<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Base\RuntimeManager;
use RZP\Models\Merchant\Cron\Jobs\BvsCronJob;
use RZP\Models\Merchant\Cron\Jobs\FOHRemovalCronJob;
use RZP\Models\Merchant\Cron\Jobs\MonthFirstMtuCronJob;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\SignupStartedCronJob;
use RZP\Models\Merchant\Cron\Jobs\WebAttributionCronJob;
use RZP\Models\Merchant\Cron\Jobs\EmailNotVerfiedCronJob;
use RZP\Models\Merchant\Cron\Jobs\NcRevampReminderCronJob;
use RZP\Models\Merchant\Cron\Jobs\SignupAttributedCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstTouchProductCronJob;
use RZP\Models\Merchant\Cron\Jobs\EnableM2MReferralCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstPaymentOfferCronJob;
use RZP\Models\Merchant\Cron\Jobs\TransactionDetailsCronJob;
use RZP\Models\Merchant\Cron\Jobs\AppsflyerUninstallCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1FormEmailTriggerCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantAutoKycPassCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedEventReconJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1DayCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1HourCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantAutoKycFailureCronJob;
use RZP\Models\Merchant\Cron\Jobs\BankDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantAutoKycSoftLimitCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantAutoKycHardLimitCronJob;
use RZP\Models\Merchant\Cron\Jobs\AadharDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantAutoKycEscalationsCronJob;
use RZP\Models\Merchant\Cron\Jobs\FriendBuySendPurchaseEventsCronJob;
use RZP\Models\Merchant\Cron\Jobs\BVSPartlyExecutedValidationCronJob;
use RZP\Models\Merchant\Cron\Jobs\SubmerchantFirstTransactionCronJob;
use RZP\Models\Merchant\Cron\Jobs\PreActivationMerchantReleaseFundsJob;
use RZP\Models\Merchant\Cron\Jobs\WebsiteCompliancePaymentsEnabledCronJob;
use RZP\Models\Merchant\Cron\Jobs\MerchantPostFirstTransactionEventCronJob;
use RZP\Models\Merchant\Cron\Jobs\InstantlyActivatedButNotTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\SaveMerchantTransactionCountForSegmentType;
use RZP\Models\Merchant\Cron\Jobs\WebsiteComplianceGracePeriodReminderCronJob;
use RZP\Models\Merchant\Cron\Jobs\TriggerWANotificationToIntlMerchantsCronJob;

class Factory
{
    /**
     * @throws BadRequestValidationFailureException
     */
    public static function getCronProcessor(string $cronType, array $input)
    {
        $input[Constants::CRON_NAME] = $cronType;

        switch ($cronType)
        {
            case "mtu-transacted":
                return (new MtuTransactedCronJob($input));
            case "month-first-mtu-transacted":
                RuntimeManager::setMaxExecTime(1200);

                return (new MonthFirstMtuCronJob($input));
            case "mtu-transacted-recon":
                return (new MtuTransactedEventReconJob($input));
            case "first-touch-product":
                return (new FirstTouchProductCronJob($input));
            case "web-attribution":
                return (new WebAttributionCronJob($input));
            case "transaction-details":
                RuntimeManager::setMaxExecTime(900);

                return (new TransactionDetailsCronJob($input));
            case "l1-pending-hourly-notification":
                return (new L1NotSubmittedIn1HourCronJob($input));
            case "l1-pending-daily-notification":
                return (new L1NotSubmittedIn1DayCronJob($input));
            case "bank-details-pending-notification":
                return (new BankDetailsNotSubmittedCronJob($input));
            case "aadhaar-details-pending-notification":
                return (new AadharDetailsNotSubmittedCronJob($input));
            case "email-verification-pending-notification":
                return (new EmailNotVerfiedCronJob($input));
            case "mtu-pending-notification":
                return (new InstantlyActivatedButNotTransactedCronJob($input));
            case "signup-started-notification":
                return (new SignupStartedCronJob($input));
            case "bvs_cron":
                return (new BvsCronJob($input));
            case "nc_revamp_reminder":
                return (new NcRevampReminderCronJob($input));
            case "signup_attributed_cron":
                RuntimeManager::setMaxExecTime(900);

                return (new SignupAttributedCronJob($input));
            case Constants::FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME:
                return (new FriendBuySendPurchaseEventsCronJob($input));
            case Constants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME:
                return (new EnableM2MReferralCronJob($input));
            case Constants::FIRST_PAYMENT_OFFER_DAILY_NOTIFICATION:
                RuntimeManager::setMaxExecTime(1200);
                return (new FirstPaymentOfferCronJob($input));
            case Constants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB:
                // since a number of queries are fired ensure enough time is provided to complete them.
                RuntimeManager::setMaxExecTime(900);

                return (new BVSPartlyExecutedValidationCronJob($input));
            case Constants::MERCHANT_SEGMENT_TYPE_CRON_JOB_NAME:
                return (new SaveMerchantTransactionCountForSegmentType($input));
            case Constants::MERCHANT_FIRST_TRANSACTION_POST_EVENT_CRON:
                return (new MerchantPostFirstTransactionEventCronJob($input));
            case "autokyc-soft-limit":
                return (new MerchantAutoKycSoftLimitCronJob($input));
            case Constants::PRE_ACTIVATION_MERCHANT_RELEASE_FUNDS:
                RuntimeManager::setMaxExecTime(7200);
                return (new PreActivationMerchantReleaseFundsJob($input));
            case "autokyc-hard-limit":
                return (new MerchantAutoKycHardLimitCronJob($input));
            case "autokyc-escalations":
                return (new MerchantAutoKycEscalationsCronJob($input));
            case Constants::L1_FORM_EMAIL_TRIGGER_CRON_JOB_NAME:
                return (new L1FormEmailTriggerCronJob($input));
            case "appsflyer-uninstall-segment-event-push":
                return (new AppsflyerUninstallCronJob($input));
            case Constants::MERCHANT_WEBSITE_INCOMPLETE_PAYMENTS_ENABLED_CRON_JOB_NAME:
                RuntimeManager::setMaxExecTime(1200);
                return (new WebsiteCompliancePaymentsEnabledCronJob($input));
            case Constants::WEBSITE_COMPLIANCE_GRACE_PERIOD_REMINDER_JOB:
                return (new WebsiteComplianceGracePeriodReminderCronJob($input));
            case Constants::MERCHANT_AUTO_KYC_FAILURE_CRON_JOB_NAME:
                RuntimeManager::setMaxExecTime(7200);
                return (new MerchantAutoKycFailureCronJob($input) );
            case Constants::SUBMERCHANT_FIRST_TRANSACTION:
                return (new SubmerchantFirstTransactionCronJob($input));
            case Constants::MERCHANT_AUTO_KYC_PASS_CRON_JOB_NAME:
                RuntimeManager::setMaxExecTime(7200);
                return (new MerchantAutoKycPassCronJob($input));
            case Constants::FOH_REMOVAL_CRON_JOB_NAME:
                RuntimeManager::setMaxExecTime(3600);
                return (new FOHRemovalCronJob($input));
            case Constants::INTL_MERCHANTS_WA_NOTIFICATION_CRON_JOB:
                RuntimeManager::setMaxExecTime(7200);
                return (new TriggerWANotificationToIntlMerchantsCronJob($input));
        }

        throw new BadRequestValidationFailureException("invalid cron");
    }
}
