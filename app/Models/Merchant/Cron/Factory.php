<?php


namespace RZP\Models\Merchant\Cron;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Models\Merchant\Cron\Jobs\AadharDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\BankDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\BvsCronJob;
use RZP\Models\Merchant\Cron\Jobs\EmailNotVerfiedCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstTouchProductCronJob;
use RZP\Models\Merchant\Cron\Jobs\InstantlyActivatedButNotTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1DayCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1HourCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedEventReconJob;
use RZP\Models\Merchant\Cron\Jobs\SignupStartedCronJob;
use RZP\Models\Merchant\Cron\Jobs\TransactionDetailsCronJob;
use RZP\Models\Merchant\Cron\Jobs\WebAttributionCronJob;
use RZP\Models\Merchant\Cron\Jobs\FriendBuySendPurchaseEventsCronJob;
use RZP\Models\Merchant\Cron\Jobs\EnableM2MReferralCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstPaymentOfferCronJob;
use RZP\Models\Merchant\Cron\Jobs\BVSPartlyExecutedValidationCronJob;

class Factory
{
    /**
     * @throws BadRequestValidationFailureException
     */
    public static function getCronProcessor(string $cronType, array $input)
    {
        $input[Constants::CRON_NAME] = $cronType;

        switch ($cronType) {
            case "mtu-transacted":
                return (new MtuTransactedCronJob($input));
            case "mtu-transacted-recon":
                return (new MtuTransactedEventReconJob($input));
            case "first-touch-product":
                return (new FirstTouchProductCronJob($input));
            case "web-attribution":
                return (new WebAttributionCronJob($input));
            case "transaction-details":
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
            case Constants::FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME:
                return (new FriendBuySendPurchaseEventsCronJob($input));
            case Constants::ENABLE_M2M_REFERRAL_CRON_JOB_NAME:
                return (new EnableM2MReferralCronJob($input));
            case "first-payment-offer-daily-notification":
                return (new FirstPaymentOfferCronJob($input));
            case Constants::BVS_PARTLY_EXECUTED_VALIDATION_CRON_JOB:
                return (new BVSPartlyExecutedValidationCronJob($input));
        }

        throw new BadRequestValidationFailureException("invalid cron");
    }
}
