<?php


namespace RZP\Models\Merchant\Cron;



use RZP\Models\Merchant\Cron\Jobs\MtuTransactedCronJob;

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
    const MTU_TRANSACTED_CRON           = "mtu_transacted";
    const TRANSACTION_DETAILS_CRON      = "transaction_details";
    const WEB_ATTRIBUTION_CRON          = "web_attribution";

    const CONFIG = [
        self::MTU_TRANSACTED_CRON       => MtuTransactedCronJob::class,
    ];
}
