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

    const RZP_USER_EMAIL = 'rzp_user_email';

    const OAUTH_LOGIN = 'oauth_login';

    const DASHBOARD = 'dashboard';


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

    const LOGIN_2FA_WITH_OTP = "otp";

    const LOGIN_2FA_WITH_PASSWORD = "password";

    const PASSWORD = "password";
    const OTP = "otp";

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

    const SUCCESS_SUFFIX   = "_success";
    const FAILED_SUFFIX    = "_failed";
    const DURATION_SUFFIX  = "_duration";

    // TOPF BE Flows
    const SEND_SIGNUP_OTP                   = 'send_signup_otp';
    const USER_SIGNUP                       = 'user_signup';
    const USER_LOGIN                        = 'user_login';
    const SEND_LOGIN_OTP                    = 'send_login_otp';
    const VERIFY_LOGIN_OTP                  = 'verify_login_otp';
    const VERIFY_SIGNUP_OTP                 = 'verify_signup_otp';
    const OTP_LOGIN_2FA_PASSWORD            = 'otp_login_2fa_password';

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
    ];
}
