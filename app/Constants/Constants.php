<?php

namespace App\Constants;

class Constants {
    const HTTP_CLIENT                   = "http_client";

    const RZP_ACCESS_TOKEN              = 'rzp_access_token';

    const ROOT_PATH                     = "/";
    const RZP_REFRESH_TOKEN             = 'rzp_refresh_token';
    const RZP_USER_MERCHANT_REGION      = 'rzp_user_merchant_region';
    const ADMIN_EXPERIENCE_SESSION      = 'admin_experience_session';

    const DESTINATION_SHELL             = "dashboard_shell";

    const DESTINATION_PHP_BE            = "dashboard_php";

    const USER_CACHE_TTL    = 30; // 10 sec

    const PRE_SIGNUP_DETAILS_CACHE_TTL = 15; // 15 sec

    const USER_MERCHANT_DETAILS_CACHE_TTL = 15; // 15 sec
}
