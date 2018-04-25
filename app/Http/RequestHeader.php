<?php

namespace RZP\Http;

class RequestHeader
{
    const REFERER                       = 'referer';

    const USER_AGENT                    = 'user-agent';

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

    // Partner Access
    const X_RAZORPAY_PARTNER_TOKEN      = 'X-Razorpay-Partner-Token';

    // Generic
    const X_USER_AGENT                  = 'X-User-Agent';
    const X_IP_ADDRESS                  = 'X-IP-Address';
}
