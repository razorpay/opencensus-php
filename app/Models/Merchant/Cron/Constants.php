<?php


namespace RZP\Models\Merchant\Cron;

class Constants
{
    const ONBOARDING_NAMESPACE  = "onboarding";

    const CRON_NAME             = "cron_name";

    const PENDING           = "pending";
    const SUCCESS           = "success";
    const FAIL              = "fail";
    const PARTIAL_SUCCESS   = "partial_success";
    const SKIPPED           = "skipped";

    const ENABLE_M2M_REFERRAL_CRON_JOB_NAME = "enable_m2m_referral";

    const MAX_RETRIES_ALLOWED = 5;

    const FRIEND_BUY_SEND_PURCHASE_EVENTS_CRON_JOB_NAME = "friend-buy-send-purchase-events-cron";
}
