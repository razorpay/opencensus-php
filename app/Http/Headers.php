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

    const HEADERS                       = 'headers';

    const X_DASHBOARD_ADMIN_AS_MERCHANT = 'X-Dashboard-AdminLoggedInAsMerchant';

    const X_RAZORPAY_REQUEST_ID = 'X-Razorpay-Request-Id';
    
    const X_ORG_ID              = 'X-Org-Id';
}
