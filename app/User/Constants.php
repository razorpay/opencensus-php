<?php

namespace App\User;

class Constants
{
    // Google Auth Constants
    const ID = 'id';

    const DATA = 'data';

    const ERROR = 'error';

    const EMAIL = 'email';

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

    const BANKING_DEMO_USER_EMAIL = "acmecorporation2021@gmail.com";

    const FAKE_CAPTCHA = "FAKE_CAPTCHA";

    //user fetch constants
    const EXPERIMENTS        = 'experiments';
    const SPLITZ_EXPERIMENTS = 'splitz_experiments';
    const TAGS               = 'tags';
    const PAYOUTS            = 'payouts';
    const FEATURES           = 'features';
}
