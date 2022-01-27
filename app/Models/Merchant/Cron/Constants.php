<?php


namespace RZP\Models\Merchant\Cron;



use RZP\Models\Merchant\Cron\Jobs\BankDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstTouchProductCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedEventReconJob;
use RZP\Models\Merchant\Cron\Jobs\TransactionDetailsCronJob;
use RZP\Models\Merchant\Cron\Jobs\WebAttributionCronJob;
use RZP\Models\Merchant\Cron\Jobs\BvsCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1HourCronJob;
use RZP\Models\Merchant\Cron\Jobs\L1NotSubmittedIn1DayCronJob;
use RZP\Models\Merchant\Cron\Jobs\AadharDetailsNotSubmittedCronJob;
use RZP\Models\Merchant\Cron\Jobs\EmailNotVerfiedCronJob;
use RZP\Models\Merchant\Cron\Jobs\InstantlyActivatedButNotTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\SignupStartedCronJob;
use RZP\Models\Merchant\Cron\Jobs\FirstPaymentOfferCronJob;

class Constants
{

    const ONBOARDING_NAMESPACE  = "onboarding";

    const CRON_NAME             = "cron_name";

    const PENDING           = "pending";
    const SUCCESS           = "success";
    const FAIL              = "fail";
    const PARTIAL_SUCCESS   = "partial_success";
    const SKIPPED           = "skipped";

    const MAX_RETRIES_ALLOWED = 5;

    /*
     * all crons that are supported
     */
    const MTU_TRANSACTED_CRON                       = "mtu_transacted";
    const TRANSACTION_DETAILS_CRON                  = "transaction_details";
    const WEB_ATTRIBUTION_CRON                      = "web_attribution";
    const MTU_TRANSACTED_SEGMENT_RECON_CRON         = "mtu_transacted_segment_recon";
    const FIRST_TOUCH_PRODUCT_CRON                  = "first_touch_product";
    const BVS_CRON                                  = "bvs_cron";
    const L1_NOT_SUBMITTED_IN_1_HR_CRON             = "l1_not_submitted_in_1_hr";
    const L1_NOT_SUBMITTED_IN_1_DAY_CRON            = "l1_not_submitted_in_1_day";
    const BANK_DETAILS_NOT_SUBMITTED_CRON           = "bank_details_not_submitted";
    const AADHAR_DETAILS_NOT_SUBMITTED              = "aadhar_details_not_submitted";
    const EMAIL_NOT_VERIFIED                        = "email_not_verified";
    const INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED    = "instantly_activated_but_not_transacted";
    const SIGNUP_STARTED                            = "signup_started";
    const FIRST_PAYMENT_OFFER                = "first_payment_offer";

    const CONFIG = [
        self::MTU_TRANSACTED_CRON                       => MtuTransactedCronJob::class,
        self::MTU_TRANSACTED_SEGMENT_RECON_CRON         => MtuTransactedEventReconJob::class,
        self::TRANSACTION_DETAILS_CRON                  => TransactionDetailsCronJob::class,
        self::FIRST_TOUCH_PRODUCT_CRON                  => FirstTouchProductCronJob::class,
        self::WEB_ATTRIBUTION_CRON                      => WebAttributionCronJob::class,
        self::BVS_CRON                                  => BvsCronJob::class,
        self::L1_NOT_SUBMITTED_IN_1_HR_CRON             => L1NotSubmittedIn1HourCronJob::class,
        self::L1_NOT_SUBMITTED_IN_1_DAY_CRON            => L1NotSubmittedIn1DayCronJob::class,
        self::BANK_DETAILS_NOT_SUBMITTED_CRON           => BankDetailsNotSubmittedCronJob::class,
        self::AADHAR_DETAILS_NOT_SUBMITTED              => AadharDetailsNotSubmittedCronJob::class,
        self::EMAIL_NOT_VERIFIED                        => EmailNotVerfiedCronJob::class,
        self::INSTANTLY_ACTIVATED_BUT_NOT_TRANSACTED    => InstantlyActivatedButNotTransactedCronJob::class,
        self::SIGNUP_STARTED                            => SignupStartedCronJob::class,
        self::FIRST_PAYMENT_OFFER                       => FirstPaymentOfferCronJob::class,
    ];
}
