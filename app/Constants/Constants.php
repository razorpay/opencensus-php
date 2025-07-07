<?php

namespace App\Constants;

use App\Utils\RegionUtils\RegionConstants;

class Constants {
    const HTTP_CLIENT                   = "http_client";

    const RZP_ACCESS_TOKEN              = 'rzp_access_token';

    const ROOT_PATH                     = "/";
    const RZP_REFRESH_TOKEN             = 'rzp_refresh_token';
    const RZP_USER_MERCHANT_REGION      = 'rzp_user_merchant_region';
    const ADMIN_EXPERIENCE_SESSION      = 'admin_experience_session';

    const DESTINATION_SHELL             = "dashboard_shell";

    const DESTINATION_PHP_BE            = "dashboard_php";

    const DESTINATION   = "destination";

    const REDIRECT_TO   = "redirect_to";

    const EASY_DASHBOARD_URL = 'EASY_DASHBOARD_URL';

    const PG_V3_REDIRECT_URL = '/pg3/onboarding';

    const RAZORPAY_SALES_ROLE = 'razorpay_sales';

    const SALES_ASSISTED_ONBOARDING_SOURCE = 'sales_assisted_onboarding';

    const SPLITZ_EXPERIMENTS = 'splitz.experiments';

    const DISABLE_EASY_REDIRECTION_FOR_BANKING = 'DISABLE_EASY_REDIRECTION_FOR_BANKING';

    const SPLITZ_BULK_EVALUATE_PATH = 'splitz/bulkEvaluate';

    const DASHBOARD_USER_CONCURRENT_API_CALL = 'DASHBOARD_USER_CONCURRENT_API_CALL';

    const ONBOARDING_FTUX = 'ONBOARDING_FTUX';

    const ONBOARDING_FTUX_V2 = 'ONBOARDING_FTUX_V2';

    const ELIGIBLE_FOR_POS = 'ELIGIBLE_FOR_POS';

    const CHUNKED_BASED_STREAMING_DISABLED = 'CHUNKED_BASED_STREAMING_DISABLED';

    const PARTNER_AGENT_ROLE = 'partner_agent';

    const PG3_V1_ENABLED = 'PG3_V1_ENABLED';

    const SHOW_PG_V3 = 'show_pg_v3';

    const PG_V3_ONBOARDING_COMPLETE = 'pg_v3_onboarding_complete';

    const SHELL_REDIRECTION_EXPERIMENT_ID = 'SHELL_REDIRECTION_EXPERIMENT_ID';

    const CACHE_STATUS  = 'cache_status';

    const CACHE_HIT = 'hit';

    const CACHE_MISS = 'miss';

    const CACHE_NAME = 'cache_name';

    const PARTNER_INTENT_CACHE_NAME = "partner_intent_cache";

    const MERCHANT_TAG_CACHE_NAME   = "merchant_tag_cache";

    const MERCHANT_FEATURES_CACHE_NAME  = "merchant_features_cache";

    const MERCHANT_ACTIVE_CAMPAIGNS_CACHE_NAME  = "merchant_active_campaigns_cache";

    const MERCHANT_DETAILS_CACHE_NAME   = "merchant_details_cache";

    const REDIRECTION_URL_CACHE_NAME = "redirection_url_cache";

    const NEW_AUTH_REARCH = 'NEW_AUTH_REARCH';
    const BIN_SERVICE_UPLOAD_ROUTE = '/upload';

    const RZP_CROSS_REGION = 'rzp_cross_region_enabled';

    // Cookie Values for Region Management
    const CROSS_REGION_COOKIE_VALUE_TRUE = 'true';
    const CROSS_REGION_COOKIE_VALUE_FALSE = 'false';

}
