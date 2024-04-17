<?php

namespace App\Http;

class Headers
{
    const CSRF_TOKEN                    = 'X-CSRF-TOKEN';

    const JWT_TOKEN                     = 'X-JWT-TOKEN';

    const OAUTH_SOURCE                  = 'X-OAUTH-SOURCE';
    // The rzpctx-dev-serve-user header is passed to upstream service for routing to
    // devserve environment if applicable.
    const DEV_SERVE_USER                = 'rzpctx-dev-serve-user';

    // The splitz project id header is passed to upstream service
    // for putting the experiments in the right project.
    const X_SPLITZ_PROJECT                = 'X-Splitz-Project';
    const ADMIN_USER_PERMISSION           = 'admin-user-permission';

    const HEADERS                       = 'headers';

    const X_DASHBOARD_ADMIN_AS_MERCHANT = 'X-Dashboard-AdminLoggedInAsMerchant';

    const X_RAZORPAY_REQUEST_ID = 'X-Razorpay-Request-Id';

    const X_ORG_ID              = 'X-Org-Id';

    // x-partner-* headers contain meta data used during phantom signup
    const X_PARTNER_APPLICATION_ID      = 'x-partner-application-id';

    const X_PARTNER_OAUTH_REFERRAL      = 'x-partner-oauth-referral';

    const ONBOARDING_SIGNATURE          = 'x-onboarding-signature';
}
