<?php

namespace RZP\Http;

class RequestHeader
{
    const REFERER                       = 'referer';

    const USER_AGENT                    = 'user-agent';

    const CONTENT_TYPE                  = 'Content-Type';

    // Dashboard Headers
    const ADMIN_TOKEN                   = 'x-admin-token';

    const DASHBOARD_HEADER_PREFIX       = 'x-dashboard';

    const MERCHANT                      = 'x-dashboard-merchant';

    // Is dashboard?
    const X_DASHBOARD                   = 'X-Dashboard';

    // Admin details
    const X_DASHBOARD_ADMIN_USERNAME    = 'X-Dashboard-Admin-Username';
    const X_DASHBOARD_ADMIN_EMAIL       = 'X-Dashboard-Admin-Email';
    const X_ADMIN_TOKEN                 = 'X-Admin-Token';

    // Merchant details
    const X_DASHBOARD_USER_ID           = 'X-Dashboard-User-Id';
    const X_DASHBOARD_USER_EMAIL        = 'X-Dashboard-User-Email';
    const X_DASHBOARD_USER_ROLE         = 'X-Dashboard-User-Role';

    /**
     * To support Account Auth: Allows API requests to be served under the
     * scope of a merchant ID that is sent as the value to this header
     *
     * On Privilege auth                - set to any merchant ID
     * On admin auth                    - set to any merchant under the current org
     * For private auth (marketplace)   - set to any linked account under the merchant
     */
    const X_RAZORPAY_ACCOUNT            = 'X-Razorpay-Account';

    // Generic
    const X_USER_AGENT                  = 'X-User-Agent';
    const X_IP_ADDRESS                  = 'X-IP-Address';

    // Request origin sent by the dashboard to determine if a request is from banking or dashboard.
    const X_REQUEST_ORIGIN              = 'X-Request-Origin';

    const X_Batch_Id                    = 'x-batch-id';
    const X_IDEMPOTENT_KEY              = 'X-Idempotent-Key';

    const X_TASK_ID                     = 'X-Task-ID';
}
