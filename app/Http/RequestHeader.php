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
    // Marketplace
    const X_RAZORPAY_ACCOUNT            = 'X-Razorpay-Account';
    // Generic
    const X_USER_AGENT                  = 'X-User-Agent';
    const X_IP_ADDRESS                  = 'X-IP-Address';
}