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
    const X_USER_EMAIL                  = 'X-User-Email';
    const X_ADMIN_TOKEN                 = 'X-Admin-Token';

    // Merchant details
    const X_DASHBOARD_USER_ID           = 'X-Dashboard-User-Id';
    const X_DASHBOARD_USER_EMAIL        = 'X-Dashboard-User-Email';
    const X_DASHBOARD_USER_ROLE         = 'X-Dashboard-User-Role';

    const X_DASHBOARD_IP                = 'X-Dashboard-Ip';

    const X_ENTITY_ID                   = 'X-Entity-Id';

    // Is user's identity verfied using 2FA?
    const X_DASHBOARD_USER_2FA_VERIFIED = 'X-Dashboard-User-2FA-Verified';

    // Is admin making request as merchant
    const X_DASHBOARD_ADMIN_AS_MERCHANT = 'X-Dashboard-AdminLoggedInAsMerchant';

    /**
     * To support Account Auth: Allows API requests to be served under the
     * scope of a merchant ID that is sent as the value to this header
     *
     * On Privilege auth                - set to any merchant ID
     * On admin auth                    - set to any merchant under the current org
     * For private auth (marketplace)   - set to any linked account under the merchant
     */
    const X_RAZORPAY_ACCOUNT            = 'X-Razorpay-Account';

    // devstack header
    const DEV_SERVE_USER             = 'rzpctx-dev-serve-user';

    // Generic
    const X_USER_AGENT                  = 'X-User-Agent';
    const X_IP_ADDRESS                  = 'X-IP-Address';

    // Determine confirm email through OTP or Link
    const X_SEND_EMAIL_OTP              = 'X-Send-Email-Otp';

    // Request origin sent by the dashboard to determine if a request is from banking or dashboard.
    const X_REQUEST_ORIGIN              = 'X-Request-Origin';

    const X_Batch_Id                    = 'x-batch-id';

    const X_Batch_Row_Id                = 'x-batch-row-id';

    const X_SLACK_REQUEST_TIMESTAMP     = 'X-Slack-Request-Timestamp';
    const X_SLACK_SIGNATURE             = 'X-Slack-Signature';

    //UserId is passed through X_Creator_Id from batch service
    const X_Creator_Id                  = 'x-creator-id';
    const X_Creator_Type                = 'x-creator-type';

    const X_PAYOUT_IDEMPOTENCY          = 'X-Payout-Idempotency';

    const X_PAYOUT_BATCH_IDEMPOTENCY    = 'X-Payout-Batch-Idempotency';

    const X_TRANSFER_IDEMPOTENCY        = 'X-Transfer-Idempotency';

    const X_TASK_ID                     = 'X-Task-ID';
    const X_SERVICE_ID                  = 'X-Service-ID';

    const X_RAZORPAY_TRACKID            = 'X-Razorpay-TrackId';

    const AUTHORIZATION                 = 'AUTHORIZATION';
    const BEARER                        = 'Bearer';
    /**
     * Request header for Typeform auth
     */

    const PAYPAL_SIGNATURE = 'PAYPAL-TRANSMISSION-SIG';
    const PAYPAL_AUTH_ALGO = 'PAYPAL-AUTH-ALGO';
    const PAYPAL_CERT_URL  = 'PAYPAL-CERT-URL';
    const PAYPAL_TRANSMISSION_ID = 'PAYPAL-TRANSMISSION-ID';
    const PAYPAL_TRANSMISSION_TIME = 'PAYPAL-TRANSMISSION-TIME';

    const TYPEFORM_SIGNATURE             =  'typeform-signature';

    // For testing purpose
    const X_RZP_TESTCASE_ID              = 'X-RZP-TESTCASE-ID';

    // For Barricade Flow
    const X_BARRICADE_FLOW              = 'x-barricade-flow';

    const X_RZP_REARCH_ORDER_TESTCASE_ID = 'X-RZP-REARCH-ORDER-TESTCASE-ID';

    const X_REQUEST_TRACE_ID             = 'X-Request-TraceId';

    const X_AMAZON_TRACE_ID              = 'X-Amzn-Trace-Id';

    const X_AMAZON_TLS_VERSION           = 'x-amzn-tls-version';

    const X_RAZORPAY_REQUEST_ID          = 'X-Razorpay-Request-Id';

    const X_MOBILE_OAUTH                 = 'x-mobile-oauth';

    const ACCEPT_VERSION                 = 'Accept-Version';
}
