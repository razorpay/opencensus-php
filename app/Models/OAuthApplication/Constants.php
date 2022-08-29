<?php


namespace RZP\Models\OAuthApplication;


class Constants
{
    const ITEMS                  = 'items';

    const TYPE                   = 'type';

    const NAME                   = 'name';

    const WEBSITE                = 'website';

    const APPLE_WATCH_APP = [
        self::NAME          =>  'Apple Watch',
        self::TYPE          =>  'apple_watch',
        self::WEBSITE       =>  'https://www.razorpay.com/x',
    ];

    // RX Mobile Token Config
    const MOBILE_APP = [
        self::NAME          =>  'RX Mobile',
        self::TYPE          =>  'mobile_app',
        self::WEBSITE       =>  'https://www.razorpay.com/x',
    ];

    // Razorpay Oauth Mobile Keys
    const X_MOBILE_ACCESS_TOKEN          = 'x_mobile_access_token';
    const X_MOBILE_REFRESH_TOKEN         = 'x_mobile_refresh_token';
    const X_MOBILE_CLIENT_ID             = 'x_mobile_client_id';
    const CURRENT_MERCHANT_ID            = 'current_merchant_id';
    const MERCHANT_ID                    = 'merchant_id';

    // Tokens
    const ACCESS_TOKEN                   = 'access_token';
    const REFRESH_TOKEN                  = 'refresh_token';

    // Token Keys
    const OAUTH_TOKEN_TYPE                      = 'oauth_token_type';
    const OAUTH_TOKEN_SCOPE                     = 'oauth_token_scope';
    const OAUTH_TOKEN_GRANT_TYPE                = 'oauth_token_grant_type';
    const OAUTH_TOKEN_MODE                      = 'oauth_token_mode';
    const OAUTH_APP_NAME                        = 'oauth_app_name';
    const OAUTH_APP_TYPE                        = 'oauth_app_type';
    const OAUTH_APP_WEBSITE                     = 'oauth_app_website';

    // RX Mobile Token Config
    const RX_MOBILE_TOKEN_TYPE                      = 'mobile_app';
    const RX_MOBILE_TOKEN_SCOPE                     = 'x_mobile_app';
    const RX_MOBILE_TOKEN_GRANT_TYPE                = 'mobile_app_client_credentials';
    const RX_MOBILE_TOKEN_MODE                      = 'live';

    // RX Mobile Token Config
    const RX_MOBILE_APP_NAME                         = 'RX Mobile';
    const RX_MOBILE_APP_TYPE                         = 'mobile_app';
    const RX_MOBILE_APP_WEBSITE                      = 'https://www.razorpay.com/x';

    // RX Mobile 2fa Config
    const RX_MOBILE_APP_2FA_TOKEN_SCOPE              = 'x_mobile_app_2fa_token';

    // RX Mobile Refresh Token Grant Type
    const RX_MOBILE_REFRESH_TOKEN_GRANT_TYPE         = 'mobile_app_refresh_token';

    const CLIENT_ID    = 'client_id';
}
