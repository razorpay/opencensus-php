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

    const SEND_SIGNUP_OTP_SUCCESS = 'send_signup_otp_success';
    const SEND_SIGNUP_OTP_FAILED = 'send_signup_otp_failed';
    const USER_SIGNUP_SUCCESS = 'user_signup_success';
    const USER_SIGNUP_FAILED = 'user_signup_failed';
    const USER_LOGIN_SUCCESS = 'user_login_success';
    const USER_LOGIN_FAILED = 'user_login_failed';
    const SEND_LOGIN_OTP_SUCCESS = 'send_login_otp_success';
    const SEND_LOGIN_OTP_FAILED = 'send_login_otp_failed';
    const VERIFY_LOGIN_OTP_SUCCESS = 'verify_login_otp_success';
    const VERIFY_LOGIN_OTP_FAILED = 'verify_login_otp_failed';
    const VERIFY_SIGNUP_OTP_SUCCESS = 'verify_signup_otp_success';
    const VERIFY_SIGNUP_OTP_FAILED = 'verify_signup_otp_failed';


    const TRACE_DETAILS_MAP = [
        self::SEND_SIGNUP_OTP_SUCCESS => [
            "success" => true,
            "method" => self::OTP,
            "traceCode" => TraceCode::SEND_SIGNUP_OTP_SUCCESS,
            "metricConstant" => MetricConstants::SEND_SIGNUP_OTP_SUCCESS_COUNT,
            "metricDurationConstant" => MetricConstants::SEND_SIGNUP_OTP_DURATION,
            "isLogin" => false,
        ],
        self::SEND_SIGNUP_OTP_FAILED => [
            'success' => false,
            "method" => self::OTP,
            'traceCode' => TraceCode::SEND_SIGNUP_OTP_FAILED,
            'metricConstant' => MetricConstants::SEND_SIGNUP_OTP_FAILED_COUNT,
            'metricDurationConstant' => MetricConstants::SEND_SIGNUP_OTP_DURATION,
            'isLogin' => false,
        ],
         self::USER_SIGNUP_SUCCESS => [
             'success' => true,
             "method" => self::PASSWORD,
             'traceCode' => TraceCode::USER_SIGNUP_SUCCESS,
             'metricConstant' => MetricConstants::USER_SIGNUP_SUCCESS_COUNT,
             'metricDurationConstant' => MetricConstants::USER_SIGNUP_DURATION,
             'isLogin' => false,
         ],
         self::USER_SIGNUP_FAILED => [
             'success' => false,
             "method" => self::PASSWORD,
             'traceCode' => TraceCode::USER_SIGNUP_FAILED,
             'metricConstant' => MetricConstants::USER_SIGNUP_FAILED_COUNT,
             'metricDurationConstant' => MetricConstants::USER_SIGNUP_DURATION,
             'isLogin' => false,
         ],
        self::USER_LOGIN_SUCCESS => [
            'success' => true,
            "method" => self::PASSWORD,
            'traceCode' => TraceCode::USER_LOGIN_SUCCESS,
            'metricConstant' => MetricConstants::USER_LOGIN_SUCCESS_COUNT,
            'metricDurationConstant' => MetricConstants::USER_LOGIN_DURATION,
            'isLogin' => true,
        ],
        self::USER_LOGIN_FAILED => [
            'success' => false,
            "method" => self::PASSWORD,
            'traceCode' => TraceCode::USER_LOGIN_FAILED,
            'metricConstant' => MetricConstants::USER_LOGIN_FAILED_COUNT,
            'metricDurationConstant' => MetricConstants::USER_LOGIN_DURATION,
            'isLogin' => true,
        ],
        self::SEND_LOGIN_OTP_SUCCESS => [
            'success' => true,
            "method" => self::OTP,
            'traceCode' => TraceCode::SEND_LOGIN_OTP_SUCCESS,
            'metricConstant' => MetricConstants::SEND_LOGIN_OTP_SUCCESS_COUNT,
            'metricDurationConstant' => MetricConstants::SEND_LOGIN_OTP_DURATION,
            'isLogin' => true,
        ],
        self::SEND_LOGIN_OTP_FAILED => [
            'success' => true,
            "method" => self::OTP,
            'traceCode' => TraceCode::SEND_LOGIN_OTP_FAILED,
            'metricConstant' => MetricConstants::SEND_LOGIN_OTP_FAILED_COUNT,
            'metricDurationConstant' => MetricConstants::SEND_LOGIN_OTP_DURATION,
            'isLogin' => true,
        ],
        self::VERIFY_LOGIN_OTP_SUCCESS => [
            'success' => true,
            "method" => self::OTP,
            'traceCode' => TraceCode::VERIFY_LOGIN_OTP_SUCCESS,
            'metricConstant' => MetricConstants::VERIFY_LOGIN_OTP_SUCCESS_COUNT,
            'metricDurationConstant' => MetricConstants::VERIFY_LOGIN_OTP_DURATION,
            'isLogin' => true,
        ],
        self::VERIFY_LOGIN_OTP_FAILED => [
            'success' => false,
            "method" => self::OTP,
            'traceCode' => TraceCode::VERIFY_LOGIN_OTP_FAILED,
            'metricConstant' => MetricConstants::VERIFY_LOGIN_OTP_FAILED_COUNT,
            'metricDurationConstant' => MetricConstants::VERIFY_LOGIN_OTP_DURATION,
            'isLogin' => true,
        ],
        self::VERIFY_SIGNUP_OTP_SUCCESS => [
            'success' => true,
            "method" => self::OTP,
            'traceCode' => TraceCode::VERIFY_SIGNUP_OTP_SUCCESS,
            'metricConstant' => MetricConstants::VERIFY_SIGNUP_OTP_SUCCESS_COUNT,
            'metricDurationConstant' => MetricConstants::VERIFY_SIGNUP_OTP_DURATION,
            'isLogin' => false,
        ],
        self::VERIFY_SIGNUP_OTP_FAILED => [
            'success' => false,
            "method" => self::OTP,
            'traceCode' => TraceCode::VERIFY_SIGNUP_OTP_FAILED,
            'metricConstant' => MetricConstants::VERIFY_SIGNUP_OTP_FAILED_COUNT,
            'metricDurationConstant' => MetricConstants::VERIFY_SIGNUP_OTP_DURATION,
            'isLogin' => false,
        ]
    ];
}
