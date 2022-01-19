<?php


namespace RZP\Models\Merchant\Cron;



use RZP\Models\Merchant\Cron\Jobs\FirstTouchProductCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedCronJob;
use RZP\Models\Merchant\Cron\Jobs\MtuTransactedEventReconJob;
use RZP\Models\Merchant\Cron\Jobs\TransactionDetailsCronJob;
use RZP\Models\Merchant\Cron\Jobs\WebAttributionCronJob;
use RZP\Models\Merchant\Cron\Jobs\BvsCronJob;

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
    const MTU_TRANSACTED_SEGMENT_RECON_CRON     = "mtu_transacted_segment_recon";
    const FIRST_TOUCH_PRODUCT_CRON              = "first_touch_product";
    const BVS_CRON                              = "bvs_cron";

    const CONFIG = [
        self::MTU_TRANSACTED_CRON               => MtuTransactedCronJob::class,
        self::MTU_TRANSACTED_SEGMENT_RECON_CRON => MtuTransactedEventReconJob::class,
        self::TRANSACTION_DETAILS_CRON          => TransactionDetailsCronJob::class,
        self::FIRST_TOUCH_PRODUCT_CRON          => FirstTouchProductCronJob::class,
        self::WEB_ATTRIBUTION_CRON              => WebAttributionCronJob::class,
        self::BVS_CRON                          => BvsCronJob::class,
    ];
}
