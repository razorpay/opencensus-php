<?php

namespace App\User;

use App\Trace\TraceCode;
use App\Metrics\Constants as MetricConstants;


class Constants
{
    // Google Auth Constants
    const ID = 'id';

    const DATA = 'data';

    const ERROR = 'error';

    const EMAIL = 'email';

    const CONTACT_MOBILE = 'contact_mobile';

    const USER_ID = 'user_id';

    const SESSION = 'session';

    const PAYLOAD = 'payload';

    const POST_METHOD = 'POST';

    const ID_TOKEN = 'id_token';

    const INTERNAL = '_internal';

    const DESCRIPTION = 'description';

    const MERCHANT_ID = 'merchant_id';

    const MERCHANT_IDS = 'merchantIds';

    const USER_DETAILS = 'user_details';

    const OAUTH_PROVIDER = 'oauth_provider';

    const OAUTH_SOURCE = 'oauth_source';

    const REFERRAL_CODE = 'referral_code';

    const RZP_USER_EMAIL = 'rzp_user_email';

    const OAUTH_LOGIN = 'oauth_login';

    const DASHBOARD = 'dashboard';

    const DASHBOARD_PROD = 'dashboard.razorpay.com';

    const DASHBOARD_PREFIX = 'dashboard-';

    const DASHBOARD_SUFFIX_DEV = '.dev.razorpay.in';

    const DASHBOARD_SUFFIX_INT_DEV = '.int.dev.razorpay.in';

    const DASHBOARD_SUFFIX_PROD = '.razorpay.com';

    const DASHBOARD_DEV = 'dashboard.dev.razorpay.in';

    const DASHBOARD_INT_DEV = 'dashboard.int.dev.razorpay.in';

    const CURLEC_PROD = 'curlec.razorpay.com';

    const CURLEC_COM = 'curlec.com';

    const CURLEC_DEV = 'dashboard-curlec.dev.razorpay.in';

    const DEFAULT_MERCHANT_ID = 'default_merchant_id';


    /**
     * determines the 2fa verification state of user in session
     * If true, means routes requiring 2fa in API will pass
     */
    const TWO_FA_VERIFIED = 'two_fa_verified';

    const INTERNAL_ERROR_CODE = 'internal_error_code';

    const NO_DB_RECORDS_FOUND = 'No db records found.';

    const DASHBOARD_USER_PAYLOAD = 'dashboard_user_payload';

    /**
     * during google oauth login/signup for the first time, if this
     * flag is set to true than invalidate all other active user sessions
     */
    const INVALIDATE_SESSIONS = 'invalidate_sessions';

    const CURRENT_SESSION = 'current_session';

    // Routes
    const OAUTH_LOGIN_ROUTE = 'users/oauth-login';

    const OAUTH_REGISTER_ROUTE = 'users/oauth-register';

    // Error Codes
    const GOOGLE_SIGN_IN_ERROR = 'Error connecting to Google. Please try again';

    const LOGIN_FAILED_CHECK_CREDENTIALS = 'User Login Failed, Please check your login credentials.';

    const ACCOUNT_DOES_NOT_EXIST = 'Razorpay Account Not Found.';

    const NETWORK_ISSUE_RELOAD_PAGE = 'Network issue, please reload the page.';

    const USER_ID_DEBUG_ACTIVATION_ISSUE = 'DqVrGiqepp5gxF';

    // RazorX Experiments and config keys
    // Experiments


    const REQUEST_ORIGIN = 'request_origin';
    // if a merchant signs up after configured `timestamp_threshold`,
    // the merchant is considered a new user. The timestamp
    // is usally around after when the code is deployed.
    const TIMESTAMP_THRESHOLD = 'timestamp_threshold';
    const DEFAULT_RESULT = 'default_result';

    const WHITELIST_CAPTCHA_EMAILS = [
        "qa+dashboard@razorpay.com",
        "qa+uiautomation@razorpay.com",
        "qa+rzp@razorpay.com",
        "annapurna.pal+008@razorpay.com"
    ];

    const SPLITZ_API_CACHING_ENABLED = 'SPLITZ_API_CACHING_ENABLED';
    const VARIABLES = 'variables';
    const RESULT = 'result';

    // browser details of the user logging in
    const BROWSER_DETAILS = 'browser_details';
    const DEVICE = 'device';
    const BROWSER = 'browser';
    const OS = 'os';

    const BANKING_DEMO_USER_EMAIL = "acmecorporation2021@gmail.com";

    const FAKE_CAPTCHA = "FAKE_CAPTCHA";

    //user fetch constants
    const EXPERIMENTS        = 'experiments';
    const SPLITZ_EXPERIMENTS = 'splitz_experiments';
    const TAGS               = 'tags';
    const PAYOUTS            = 'payouts';
    const FEATURES           = 'features';
    const MERCHANT_DETAILS   = 'merchant_details';

    const CAMPAIGNS          = 'campaigns';

    const LOGIN_2FA_WITH_OTP = "otp";

    const LOGIN_2FA_WITH_PASSWORD = "password";

    const PASSWORD  = "password";
    const OTP       = "otp";
    const OAUTH     = "oauth";

    // trace & metric constants
    const SUCCESS                   = "success";
    const FAILED                    = "failed";
    const DURATION                  = "duration";
    const ERROR_CODE                = "error_code";
    const IS_LOGIN                  = "is_login";
    const METHOD                    = "method";
    const TRACE_CODE                = "trace_code";
    const METRIC_CONSTANT           = "metric_constant";
    const METRIC_DURATION_CONSTANT  = "metric_duration_constant";
    const APP                       = "app";
    const WEBSITE                   = "website";
    const UNKNOWN_PLATFORM          = "unknown_platform";
    const HITS                      = "hits";
    const MISS                      = "miss";

    const CACHE_KEY                 = "cache_key";
    const ROUTE_NAME                = "route_name";

    const SUCCESS_SUFFIX   = "_success";
    const FAILED_SUFFIX    = "_failed";
    const DURATION_SUFFIX  = "_duration";

    const REDIS_CACHE_PREFIX = 'dashboard_:';

    // TOPF BE Flows
    const SEND_SIGNUP_OTP                   = 'send_signup_otp';
    const USER_SIGNUP                       = 'user_signup';
    const USER_LOGIN                        = 'user_login';
    const SEND_LOGIN_OTP                    = 'send_login_otp';
    const VERIFY_LOGIN_OTP                  = 'verify_login_otp';
    const VERIFY_SIGNUP_OTP                 = 'verify_signup_otp';
    const OTP_LOGIN_2FA_PASSWORD            = 'otp_login_2fa_password';
    const PASSWORD_LOGIN_2FA_OTP            = 'password_login_2fa_otp';
    const USER_OAUTH_LOGIN                  = 'user_oauth_login';
    const USER_OAUTH_SIGNUP                 = 'user_oauth_signup';

    const SEND_SIGNUP_OTP_SUCCESS           = 'send_signup_otp_success';
    const SEND_SIGNUP_OTP_FAILED            = 'send_signup_otp_failed';
    const USER_SIGNUP_SUCCESS               = 'user_signup_success';
    const USER_SIGNUP_FAILED                = 'user_signup_failed';
    const USER_LOGIN_SUCCESS                = 'user_login_success';
    const USER_LOGIN_FAILED                 = 'user_login_failed';
    const SEND_LOGIN_OTP_SUCCESS            = 'send_login_otp_success';
    const SEND_LOGIN_OTP_FAILED             = 'send_login_otp_failed';
    const VERIFY_LOGIN_OTP_SUCCESS          = 'verify_login_otp_success';
    const VERIFY_LOGIN_OTP_FAILED           = 'verify_login_otp_failed';
    const VERIFY_SIGNUP_OTP_SUCCESS         = 'verify_signup_otp_success';
    const VERIFY_SIGNUP_OTP_FAILED          = 'verify_signup_otp_failed';
    const OTP_LOGIN_2FA_PASSWORD_SUCCESS    = 'otp_login_2fa_password_success';
    const OTP_LOGIN_2FA_PASSWORD_FAILED     = 'otp_login_2fa_password_failed';
    const PASSWORD_LOGIN_2FA_OTP_SUCCESS    = 'password_login_2fa_otp_success';
    const PASSWORD_LOGIN_2FA_OTP_FAILED     = 'password_login_2fa_otp_failed';
    const USER_OAUTH_LOGIN_SUCCESS          = 'user_oauth_login_success';
    const USER_OAUTH_LOGIN_FAILED           = 'user_oauth_login_failed';
    const USER_OAUTH_SIGNUP_SUCCESS         = 'user_oauth_signup_success';
    const USER_OAUTH_SIGNUP_FAILED          = 'user_oauth_signup_failed';
    const DASHBOARD_APP                     = 'dashboard_app';
    const SIGNUP_SOURCE                     = 'signup_source';
    const REQUEST_SOURCE                    = 'request_source';

    const RAZORX_CACHING_ENABLED            = 'RAZORX_CACHING_ENABLED';

    const SESSION_WHITELISTED_ERROR_CODES = [
        'BAD_REQUEST_USER_2FA_LOGIN_PASSWORD_REQUIRED',
        'BAD_REQUEST_2FA_LOGIN_PASSWORD_SUSPENDED',
        'BAD_REQUEST_2FA_LOGIN_INCORRECT_PASSWORD',
        'BAD_REQUEST_2FA_LOGIN_INCORRECT_OTP',
        'BAD_REQUEST_USER_2FA_LOGIN_OTP_REQUIRED',
        'BAD_REQUEST_USER_LOGIN_2FA_SETUP_REQUIRED',
    ];

    const EPOS_APP_DEPRECATED_MESSAGE = [
        [
            "" => "App no longer supported. Download Razorpay app from Play Store",
            "internal_error_code" => "0",
        ]
    ];

    // PG routes called while rendering. These routes are specific to PG and not called by X.
    const PG_DASHBOARD_RENDER_ROUTES = [
        self::DASHBOARD,
        self::DASHBOARD_APP,
    ];

    // Adding Server Names of orgs which do not want to call FIELDS_DECOUPLED_FOR_PG_RENDERING fields while rendering
    const PG_DASHBOARD_SERVER_NAMES = [
        'dashboard.razorpay.com',
        'dashboard.dev.razorpay.in',
    ];

    const BLOCKED_EMAILS_FOR_LOGIN = [
        'tusharvijayworld@gmail.com',
    ];

    const BLOCKED_NUMBERS_FOR_LOGIN = [
        '9460507015',
    ];

    const DOMAIN_REDIRECT_MAP = [
        'dashboard-curlec.dev.razorpay.in' => ['redirect_url' => 'https://accounts-curlec.np.razorpay.in', 'id' => 'CURLEC_REDIRECTION_ENABLED'],
        'dashboard.curlec.com'             => ['redirect_url' => 'https://accounts.curlec.com', 'id'=>'CURLEC_REDIRECTION_ENABLED'],
    ];

    const PHONE_NUMBER_EXTENSIONS = [
        '', '+91', '91', '0'
    ];

    const PARTNER_ACTIVATION_APPLICABLE_TYPES = ['reseller'];
    // Fields to be added if API calls are to be skipped for them.
    // Since we want to adopt the changes in a phase-wise manner, commenting out the fields.
    // Will uncomment when they are to be excluded from the user data
    const FIELDS_DECOUPLED_FOR_PG_RENDERING = [
        self::FEATURES,
        self::CAMPAIGNS,
        self::TAGS,

        // self::EXPERIMENTS,
        // self::SPLITZ_EXPERIMENTS,
    ];

    const TRACE_DETAILS_MAP = [
        self::SEND_SIGNUP_OTP => [
            self::SEND_SIGNUP_OTP_SUCCESS =>   [
                self::METHOD                    => self::OTP,
                self::TRACE_CODE                => TraceCode::SEND_SIGNUP_OTP_SUCCESS,
                self::METRIC_CONSTANT           => MetricConstants::SEND_SIGNUP_OTP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT  => MetricConstants::SEND_SIGNUP_OTP_DURATION,
                self::IS_LOGIN                  => false,
            ],
            self::SEND_SIGNUP_OTP_FAILED => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::SEND_SIGNUP_OTP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::SEND_SIGNUP_OTP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::SEND_SIGNUP_OTP_DURATION,
                self::IS_LOGIN => false,
            ],
        ],
        self::USER_SIGNUP => [
            self::USER_SIGNUP_SUCCESS => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::USER_SIGNUP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::USER_SIGNUP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_SIGNUP_DURATION,
                self::IS_LOGIN => false,
            ],
            self::USER_SIGNUP_FAILED => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::USER_SIGNUP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::USER_SIGNUP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_SIGNUP_DURATION,
                self::IS_LOGIN => false,
            ],
        ],
        self::USER_LOGIN => [
            self::USER_LOGIN_SUCCESS => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::USER_LOGIN_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::USER_LOGIN_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_LOGIN_DURATION,
                self::IS_LOGIN => true,
            ],
            self::USER_LOGIN_FAILED => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::USER_LOGIN_FAILED,
                self::METRIC_CONSTANT => MetricConstants::USER_LOGIN_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_LOGIN_DURATION,
                self::IS_LOGIN => true,
            ],
        ],
        self::SEND_LOGIN_OTP => [
            self::SEND_LOGIN_OTP_SUCCESS => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::SEND_LOGIN_OTP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::SEND_LOGIN_OTP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::SEND_LOGIN_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
            self::SEND_LOGIN_OTP_FAILED => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::SEND_LOGIN_OTP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::SEND_LOGIN_OTP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::SEND_LOGIN_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
        ],
        self::VERIFY_LOGIN_OTP => [
            self::VERIFY_LOGIN_OTP_SUCCESS => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::VERIFY_LOGIN_OTP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::VERIFY_LOGIN_OTP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::VERIFY_LOGIN_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
            self::VERIFY_LOGIN_OTP_FAILED => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::VERIFY_LOGIN_OTP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::VERIFY_LOGIN_OTP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::VERIFY_LOGIN_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
        ],
        self::VERIFY_SIGNUP_OTP => [
            self::VERIFY_SIGNUP_OTP_SUCCESS => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::VERIFY_SIGNUP_OTP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::VERIFY_SIGNUP_OTP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::VERIFY_SIGNUP_OTP_DURATION,
                self::IS_LOGIN => false,
            ],
            self::VERIFY_SIGNUP_OTP_FAILED => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::VERIFY_SIGNUP_OTP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::VERIFY_SIGNUP_OTP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::VERIFY_SIGNUP_OTP_DURATION,
                self::IS_LOGIN => false,
            ],
        ],
        self::OTP_LOGIN_2FA_PASSWORD => [
            self::OTP_LOGIN_2FA_PASSWORD_SUCCESS => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::OTP_LOGIN_2FA_PASSWORD_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::OTP_LOGIN_2FA_PASSWORD_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::OTP_LOGIN_2FA_PASSWORD_DURATION,
                self::IS_LOGIN => true,
            ],
            self::OTP_LOGIN_2FA_PASSWORD_FAILED => [
                self::METHOD => self::PASSWORD,
                self::TRACE_CODE => TraceCode::OTP_LOGIN_2FA_PASSWORD_FAILED,
                self::METRIC_CONSTANT => MetricConstants::OTP_LOGIN_2FA_PASSWORD_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::OTP_LOGIN_2FA_PASSWORD_DURATION,
                self::IS_LOGIN => true,
            ],
        ],
        self::PASSWORD_LOGIN_2FA_OTP => [
            self::PASSWORD_LOGIN_2FA_OTP_SUCCESS => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::PASSWORD_LOGIN_2FA_OTP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::PASSWORD_LOGIN_2FA_OTP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::PASSWORD_LOGIN_2FA_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
            self::PASSWORD_LOGIN_2FA_OTP_FAILED => [
                self::METHOD => self::OTP,
                self::TRACE_CODE => TraceCode::PASSWORD_LOGIN_2FA_OTP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::PASSWORD_LOGIN_2FA_OTP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::PASSWORD_LOGIN_2FA_OTP_DURATION,
                self::IS_LOGIN => true,
            ],
        ],
        self::USER_OAUTH_LOGIN => [
            self::USER_OAUTH_LOGIN_SUCCESS => [
                self::METHOD => self::OAUTH,
                self::TRACE_CODE => TraceCode::USER_OAUTH_LOGIN_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::USER_OAUTH_LOGIN_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_OAUTH_LOGIN_DURATION,
                self::IS_LOGIN => true,
            ],
            self::USER_OAUTH_LOGIN_FAILED => [
                self::METHOD => self::OAUTH,
                self::TRACE_CODE => TraceCode::USER_OAUTH_LOGIN_FAILED,
                self::METRIC_CONSTANT => MetricConstants::USER_OAUTH_LOGIN_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_OAUTH_LOGIN_DURATION,
                self::IS_LOGIN => true,
            ]
        ],
        self::USER_OAUTH_SIGNUP => [
            self::USER_OAUTH_SIGNUP_SUCCESS => [
                self::METHOD => self::OAUTH,
                self::TRACE_CODE => TraceCode::USER_OAUTH_SIGNUP_SUCCESS,
                self::METRIC_CONSTANT => MetricConstants::USER_OAUTH_SIGNUP_SUCCESS_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_OAUTH_SIGNUP_DURATION,
                self::IS_LOGIN => false,
            ],
            self::USER_OAUTH_SIGNUP_FAILED => [
                self::METHOD => self::OAUTH,
                self::TRACE_CODE => TraceCode::USER_OAUTH_SIGNUP_FAILED,
                self::METRIC_CONSTANT => MetricConstants::USER_OAUTH_SIGNUP_FAILED_COUNT,
                self::METRIC_DURATION_CONSTANT => MetricConstants::USER_OAUTH_SIGNUP_DURATION,
                self::IS_LOGIN => false,
            ]
        ]
    ];
}
