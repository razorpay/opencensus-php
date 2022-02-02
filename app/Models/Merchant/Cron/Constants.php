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
}
